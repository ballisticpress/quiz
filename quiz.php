<?php
/**
 * Quiz Application - Main Quiz Page
 * Displays randomized questions and handles user responses
 */

session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = "Quiz Challenge - Question";
$database = new Database();
$db = $database->getConnection();

// Check if starting a new quiz
if (isset($_GET['new']) && $_GET['new'] == '1') {
    // Clear previous quiz data
    unset($_SESSION['quiz_questions']);
    unset($_SESSION['quiz_answers']);
    unset($_SESSION['current_question']);
    unset($_SESSION['quiz_started']);
    unset($_SESSION['quiz_start_time']);
    unset($_SESSION['quiz_results']);
}

// Initialize quiz session if not started
if (!isset($_SESSION['quiz_started']) || !$_SESSION['quiz_started']) {
    // Get 10 random questions
    $stmt = $db->query("SELECT * FROM questions ORDER BY RAND() LIMIT 10");
    $questions = $stmt->fetchAll();
    
    if (empty($questions)) {
        die('No questions available. Please contact administrator.');
    }
    
    $_SESSION['quiz_questions'] = $questions;
    $_SESSION['current_question'] = 0;
    $_SESSION['quiz_answers'] = [];
    $_SESSION['quiz_started'] = true;
    $_SESSION['quiz_start_time'] = time();
}

$current_question_index = $_SESSION['current_question'];
$total_questions = count($_SESSION['quiz_questions']);
$current_question = $_SESSION['quiz_questions'][$current_question_index];

// Calculate progress
$progress_percentage = (($current_question_index + 1) / $total_questions) * 100;

include 'includes/header.php';
?>

<div id="alert-container"></div>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <!-- Progress Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">
                        <i class="bi bi-person-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <span class="badge bg-primary fs-6">
                        Question <?php echo $current_question_index + 1; ?> of <?php echo $total_questions; ?>
                    </span>
                </div>
                <div class="progress quiz-progress">
                    <div class="progress-bar bg-success" 
                         role="progressbar" 
                         style="width: <?php echo $progress_percentage; ?>%" 
                         aria-valuenow="<?php echo $progress_percentage; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Container -->
        <div id="quiz-container">
            <!-- Question Card -->
            <div class="card question-card shadow-lg border-0">
                <div class="card-header text-center py-4">
                    <h3 class="mb-0">
                        <i class="bi bi-question-circle me-2"></i>
                        Question <?php echo $current_question_index + 1; ?>
                    </h3>
                </div>
                <div class="card-body p-4">
                    <!-- Question Text -->
                    <div class="question-text mb-4">
                        <h4 class="text-white mb-0">
                            <?php echo htmlspecialchars($current_question['question_text']); ?>
                        </h4>
                    </div>

                    <!-- Answer Options -->
                    <div class="answer-options">
                        <input type="hidden" id="question-id" value="<?php echo $current_question['id']; ?>">
                        
                        <div class="answer-option" data-answer="a" role="button" tabindex="0">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-3 fs-5">A</span>
                                <span class="flex-grow-1"><?php echo htmlspecialchars($current_question['option_a']); ?></span>
                            </div>
                        </div>
                        
                        <div class="answer-option" data-answer="b" role="button" tabindex="0">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-3 fs-5">B</span>
                                <span class="flex-grow-1"><?php echo htmlspecialchars($current_question['option_b']); ?></span>
                            </div>
                        </div>
                        
                        <div class="answer-option" data-answer="c" role="button" tabindex="0">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-3 fs-5">C</span>
                                <span class="flex-grow-1"><?php echo htmlspecialchars($current_question['option_c']); ?></span>
                            </div>
                        </div>
                        
                        <div class="answer-option" data-answer="d" role="button" tabindex="0">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-3 fs-5">D</span>
                                <span class="flex-grow-1"><?php echo htmlspecialchars($current_question['option_d']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top border-light">
                        <div>
                            <?php if ($current_question_index > 0): ?>
                                <a href="?prev=1" class="btn btn-outline-light">
                                    <i class="bi bi-arrow-left me-2"></i>Previous
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <?php if ($current_question_index < $total_questions - 1): ?>
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
        </div>

        <!-- Quiz Info Card -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-muted small">Time Elapsed</div>
                        <div class="fw-bold" id="timer">00:00</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Questions Left</div>
                        <div class="fw-bold"><?php echo $total_questions - $current_question_index - 1; ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Answered</div>
                        <div class="fw-bold"><?php echo count($_SESSION['quiz_answers']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Progress</div>
                        <div class="fw-bold"><?php echo round($progress_percentage); ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Timer functionality
let startTime = <?php echo $_SESSION['quiz_start_time']; ?> * 1000;
let timerInterval;

function updateTimer() {
    const now = new Date().getTime();
    const elapsed = now - startTime;
    
    const minutes = Math.floor(elapsed / (1000 * 60));
    const seconds = Math.floor((elapsed % (1000 * 60)) / 1000);
    
    document.getElementById('timer').textContent = 
        String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
}

// Start timer
timerInterval = setInterval(updateTimer, 1000);
updateTimer();

// Handle previous question navigation
<?php if (isset($_GET['prev']) && $current_question_index > 0): ?>
    // Navigate to previous question
    fetch('quiz_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=prev_question'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
<?php endif; ?>

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    switch(e.key) {
        case '1':
        case 'a':
        case 'A':
            selectAnswerByKey('a');
            break;
        case '2':
        case 'b':
        case 'B':
            selectAnswerByKey('b');
            break;
        case '3':
        case 'c':
        case 'C':
            selectAnswerByKey('c');
            break;
        case '4':
        case 'd':
        case 'D':
            selectAnswerByKey('d');
            break;
        case 'Enter':
            if (selectedAnswer) {
                const nextBtn = document.getElementById('next-question');
                const submitBtn = document.getElementById('submit-quiz');
                if (nextBtn && !nextBtn.disabled) nextBtn.click();
                if (submitBtn && !submitBtn.disabled) submitBtn.click();
            }
            break;
    }
});

function selectAnswerByKey(answer) {
    const option = document.querySelector(`[data-answer="${answer}"]`);
    if (option) {
        selectAnswer(option);
    }
}

// Auto-save functionality
setInterval(function() {
    if (selectedAnswer) {
        // Save current state to localStorage
        localStorage.setItem('quiz_current_answer', JSON.stringify({
            question_id: document.getElementById('question-id').value,
            selected_answer: selectedAnswer,
            timestamp: new Date().toISOString()
        }));
    }
}, 5000);

// Prevent accidental page refresh
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = 'Are you sure you want to leave? Your quiz progress will be lost.';
});
</script>

<?php include 'includes/footer.php'; ?>