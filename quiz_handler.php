<?php
/**
 * Quiz Handler - AJAX endpoint for quiz interactions
 * Handles answer submissions, navigation, and quiz completion
 */

session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$action = $_POST['action'] ?? '';
$database = new Database();
$db = $database->getConnection();

try {
    switch ($action) {
        case 'submit_answer':
            handleAnswerSubmission($db);
            break;
            
        case 'submit_final':
            handleFinalSubmission($db);
            break;
            
        case 'prev_question':
            handlePreviousQuestion();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Quiz handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * Handle answer submission and move to next question
 */
function handleAnswerSubmission($db) {
    $question_id = intval($_POST['question_id'] ?? 0);
    $selected_answer = sanitizeInput($_POST['selected_answer'] ?? '');
    
    // Validate input
    if (!$question_id || !in_array($selected_answer, ['a', 'b', 'c', 'd'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    // Store answer in session
    $_SESSION['quiz_answers'][$question_id] = $selected_answer;
    
    // Move to next question
    $_SESSION['current_question']++;
    
    $current_index = $_SESSION['current_question'];
    $total_questions = count($_SESSION['quiz_questions']);
    
    if ($current_index < $total_questions) {
        // Generate next question HTML
        $next_question = $_SESSION['quiz_questions'][$current_index];
        $progress_percentage = (($current_index + 1) / $total_questions) * 100;
        
        $html = generateQuestionHTML($next_question, $current_index, $total_questions, $progress_percentage);
        
        echo json_encode([
            'success' => true,
            'next_question' => [
                'html' => $html,
                'current' => $current_index + 1,
                'total' => $total_questions
            ]
        ]);
    } else {
        // Quiz completed
        echo json_encode(['success' => true, 'next_question' => null]);
    }
}

/**
 * Handle final quiz submission
 */
function handleFinalSubmission($db) {
    $question_id = intval($_POST['question_id'] ?? 0);
    $selected_answer = sanitizeInput($_POST['selected_answer'] ?? '');
    
    // Validate input
    if (!$question_id || !in_array($selected_answer, ['a', 'b', 'c', 'd'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    // Store final answer
    $_SESSION['quiz_answers'][$question_id] = $selected_answer;
    
    // Calculate score and save to database
    $score_data = calculateScore($db);
    $attempt_id = saveQuizAttempt($db, $score_data);
    
    if ($attempt_id) {
        // Store results in session for results page
        $_SESSION['quiz_results'] = $score_data;
        $_SESSION['attempt_id'] = $attempt_id;
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save quiz results']);
    }
}

/**
 * Handle navigation to previous question
 */
function handlePreviousQuestion() {
    if ($_SESSION['current_question'] > 0) {
        $_SESSION['current_question']--;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Already at first question']);
    }
}

/**
 * Calculate quiz score
 */
function calculateScore($db) {
    $total_questions = count($_SESSION['quiz_questions']);
    $correct_answers = 0;
    $question_results = [];
    
    foreach ($_SESSION['quiz_questions'] as $question) {
        $question_id = $question['id'];
        $correct_answer = $question['correct_answer'];
        $user_answer = $_SESSION['quiz_answers'][$question_id] ?? '';
        
        $is_correct = ($user_answer === $correct_answer);
        if ($is_correct) {
            $correct_answers++;
        }
        
        $question_results[] = [
            'question_id' => $question_id,
            'question_text' => $question['question_text'],
            'correct_answer' => $correct_answer,
            'user_answer' => $user_answer,
            'is_correct' => $is_correct,
            'options' => [
                'a' => $question['option_a'],
                'b' => $question['option_b'],
                'c' => $question['option_c'],
                'd' => $question['option_d']
            ]
        ];
    }
    
    $percentage = ($correct_answers / $total_questions) * 100;
    
    return [
        'total_questions' => $total_questions,
        'correct_answers' => $correct_answers,
        'percentage' => round($percentage, 2),
        'question_results' => $question_results,
        'time_taken' => time() - $_SESSION['quiz_start_time']
    ];
}

/**
 * Save quiz attempt to database
 */
function saveQuizAttempt($db, $score_data) {
    try {
        $db->beginTransaction();
        
        // Insert quiz attempt
        $stmt = $db->prepare("
            INSERT INTO quiz_attempts (user_id, score, total_questions, percentage) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $score_data['correct_answers'],
            $score_data['total_questions'],
            $score_data['percentage']
        ]);
        
        $attempt_id = $db->lastInsertId();
        
        // Insert individual answers
        $stmt = $db->prepare("
            INSERT INTO quiz_answers (attempt_id, question_id, selected_answer, is_correct) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($score_data['question_results'] as $result) {
            $stmt->execute([
                $attempt_id,
                $result['question_id'],
                $result['user_answer'],
                $result['is_correct'] ? 1 : 0
            ]);
        }
        
        $db->commit();
        return $attempt_id;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Failed to save quiz attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML for question display
 */
function generateQuestionHTML($question, $current_index, $total_questions, $progress_percentage) {
    ob_start();
    ?>
    <!-- Question Card -->
    <div class="card question-card shadow-lg border-0">
        <div class="card-header text-center py-4">
            <h3 class="mb-0">
                <i class="bi bi-question-circle me-2"></i>
                Question <?php echo $current_index + 1; ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <!-- Question Text -->
            <div class="question-text mb-4">
                <h4 class="text-white mb-0">
                    <?php echo htmlspecialchars($question['question_text']); ?>
                </h4>
            </div>

            <!-- Answer Options -->
            <div class="answer-options">
                <input type="hidden" id="question-id" value="<?php echo $question['id']; ?>">
                
                <div class="answer-option" data-answer="a" role="button" tabindex="0">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3 fs-5">A</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_a']); ?></span>
                    </div>
                </div>
                
                <div class="answer-option" data-answer="b" role="button" tabindex="0">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3 fs-5">B</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_b']); ?></span>
                    </div>
                </div>
                
                <div class="answer-option" data-answer="c" role="button" tabindex="0">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3 fs-5">C</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_c']); ?></span>
                    </div>
                </div>
                
                <div class="answer-option" data-answer="d" role="button" tabindex="0">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3 fs-5">D</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_d']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between mt-4 pt-3 border-top border-light">
                <div>
                    <?php if ($current_index > 0): ?>
                        <a href="?prev=1" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left me-2"></i>Previous
                        </a>
                    <?php endif; ?>
                </div>
                
                <div>
                    <?php if ($current_index < $total_questions - 1): ?>
                        <button type="button" id="next-question" class="btn btn-light btn-lg" disabled>
                            Next <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    <?php else: ?>
                        <button type="button" id="submit-quiz" class="btn btn-success btn-lg" disabled>
                            <i class="bi bi-check-circle me-2"></i>Submit Quiz
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>