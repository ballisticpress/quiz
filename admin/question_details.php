<?php
/**
 * Question Details - AJAX endpoint
 * Returns detailed view of a specific question
 */

session_start();
require_once '../config/database.php';

$question_id = intval($_GET['id'] ?? 0);

if (!$question_id) {
    echo '<div class="alert alert-danger">Invalid question ID</div>';
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get question details
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();
    
    if (!$question) {
        echo '<div class="alert alert-danger">Question not found</div>';
        exit();
    }
    
    // Get usage statistics
    $stmt = $db->prepare("
        SELECT COUNT(qa.id) as usage_count,
               MIN(qat.attempt_date) as first_used,
               MAX(qat.attempt_date) as last_used
        FROM quiz_answers qa
        JOIN quiz_attempts qat ON qa.attempt_id = qat.id
        WHERE qa.question_id = ?
    ");
    $stmt->execute([$question_id]);
    $usage = $stmt->fetch();
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading question details</div>';
    exit();
}
?>

<div class="question-details">
    <!-- Question Preview -->
    <div class="card question-card mb-4">
        <div class="card-header text-center py-3">
            <h5 class="mb-0 text-white">
                <i class="bi bi-question-circle me-2"></i>
                Question Preview
            </h5>
        </div>
        <div class="card-body">
            <h5 class="text-white mb-4"><?php echo htmlspecialchars($question['question_text']); ?></h5>
            
            <div class="answer-options">
                <div class="answer-option <?php echo $question['correct_answer'] === 'a' ? 'correct' : ''; ?>" style="margin: 8px 0;">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3">A</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_a']); ?></span>
                        <?php if ($question['correct_answer'] === 'a'): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="answer-option <?php echo $question['correct_answer'] === 'b' ? 'correct' : ''; ?>" style="margin: 8px 0;">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3">B</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_b']); ?></span>
                        <?php if ($question['correct_answer'] === 'b'): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="answer-option <?php echo $question['correct_answer'] === 'c' ? 'correct' : ''; ?>" style="margin: 8px 0;">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3">C</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_c']); ?></span>
                        <?php if ($question['correct_answer'] === 'c'): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="answer-option <?php echo $question['correct_answer'] === 'd' ? 'correct' : ''; ?>" style="margin: 8px 0;">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3">D</span>
                        <span class="flex-grow-1"><?php echo htmlspecialchars($question['option_d']); ?></span>
                        <?php if ($question['correct_answer'] === 'd'): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Question Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Question Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Question ID:</strong> #<?php echo $question['id']; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Correct Answer:</strong> 
                        <span class="badge bg-success ms-2"><?php echo strtoupper($question['correct_answer']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($question['created_at'])); ?>
                    </div>
                    <div class="mb-0">
                        <strong>Character Count:</strong> <?php echo strlen($question['question_text']); ?> characters
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Usage Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($usage['usage_count'] > 0): ?>
                        <div class="mb-3">
                            <strong>Times Used:</strong> 
                            <span class="badge bg-info ms-2"><?php echo $usage['usage_count']; ?> times</span>
                        </div>
                        <div class="mb-3">
                            <strong>First Used:</strong> <?php echo date('M j, Y', strtotime($usage['first_used'])); ?>
                        </div>
                        <div class="mb-0">
                            <strong>Last Used:</strong> <?php echo date('M j, Y', strtotime($usage['last_used'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-clipboard-x fs-3 text-muted mb-2"></i>
                            <p class="text-muted mb-0">This question hasn't been used in any quizzes yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-primary" onclick="editQuestion(<?php echo $question['id']; ?>)">
            <i class="bi bi-pencil me-2"></i>Edit Question
        </button>
        <?php if ($usage['usage_count'] == 0): ?>
            <button type="button" class="btn btn-outline-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)">
                <i class="bi bi-trash me-2"></i>Delete Question
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
function editQuestion(questionId) {
    window.location.href = `edit_question.php?id=${questionId}`;
}

function deleteQuestion(questionId) {
    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        const token = '<?php echo generateCSRFToken(); ?>';
        window.location.href = `manage_questions.php?delete=1&id=${questionId}&token=${token}`;
    }
}
</script>