<?php
/**
 * Add Question Page
 * Allows administrators to add new questions to the quiz database
 */

session_start();
require_once '../config/database.php';

$page_title = "Add New Question - Quiz Management";
$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = sanitizeInput($_POST['question_text'] ?? '');
    $option_a = sanitizeInput($_POST['option_a'] ?? '');
    $option_b = sanitizeInput($_POST['option_b'] ?? '');
    $option_c = sanitizeInput($_POST['option_c'] ?? '');
    $option_d = sanitizeInput($_POST['option_d'] ?? '');
    $correct_answer = sanitizeInput($_POST['correct_answer'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    }
    // Validate input
    elseif (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error_message = 'All fields are required.';
    }
    elseif (!in_array($correct_answer, ['a', 'b', 'c', 'd'])) {
        $error_message = 'Please select a valid correct answer.';
    }
    else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer])) {
                $success_message = 'Question added successfully!';
                // Clear form data
                $question_text = $option_a = $option_b = $option_c = $option_d = $correct_answer = '';
            } else {
                $error_message = 'Failed to add question. Please try again.';
            }
            
        } catch (Exception $e) {
            $error_message = 'Database error occurred. Please try again.';
            error_log("Add question error: " . $e->getMessage());
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="display-6 mb-0">
                    <i class="bi bi-plus-circle me-3"></i>
                    Add New Question
                </h1>
                <p class="text-muted">Create a new quiz question with multiple choice answers</p>
            </div>
            <a href="/admin" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Add Question Form -->
        <div class="card shadow-lg border-0">
            <div class="card-header bg-gradient text-white py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3 class="card-title mb-0 text-center">
                    <i class="bi bi-question-circle-fill me-2"></i>
                    Question Details
                </h3>
            </div>
            <div class="card-body p-4">
                <!-- Error/Success Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Question Text -->
                    <div class="mb-4">
                        <label for="question_text" class="form-label fw-bold">
                            <i class="bi bi-chat-quote me-2"></i>Question Text
                        </label>
                        <textarea class="form-control form-control-lg" 
                                  id="question_text" 
                                  name="question_text" 
                                  rows="3" 
                                  required 
                                  placeholder="Enter the question text here..."><?php echo htmlspecialchars($question_text ?? ''); ?></textarea>
                        <div class="invalid-feedback">
                            Please provide a question text.
                        </div>
                        <div class="form-text">
                            Write a clear and concise question. Avoid ambiguous wording.
                        </div>
                    </div>

                    <!-- Answer Options -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="option_a" class="form-label fw-bold">
                                <span class="badge bg-primary me-2">A</span>Option A
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="option_a" 
                                   name="option_a" 
                                   required 
                                   value="<?php echo htmlspecialchars($option_a ?? ''); ?>"
                                   placeholder="Enter option A">
                            <div class="invalid-feedback">
                                Please provide option A.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="option_b" class="form-label fw-bold">
                                <span class="badge bg-primary me-2">B</span>Option B
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="option_b" 
                                   name="option_b" 
                                   required 
                                   value="<?php echo htmlspecialchars($option_b ?? ''); ?>"
                                   placeholder="Enter option B">
                            <div class="invalid-feedback">
                                Please provide option B.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="option_c" class="form-label fw-bold">
                                <span class="badge bg-primary me-2">C</span>Option C
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="option_c" 
                                   name="option_c" 
                                   required 
                                   value="<?php echo htmlspecialchars($option_c ?? ''); ?>"
                                   placeholder="Enter option C">
                            <div class="invalid-feedback">
                                Please provide option C.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="option_d" class="form-label fw-bold">
                                <span class="badge bg-primary me-2">D</span>Option D
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="option_d" 
                                   name="option_d" 
                                   required 
                                   value="<?php echo htmlspecialchars($option_d ?? ''); ?>"
                                   placeholder="Enter option D">
                            <div class="invalid-feedback">
                                Please provide option D.
                            </div>
                        </div>
                    </div>

                    <!-- Correct Answer Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-check-circle me-2"></i>Correct Answer
                        </label>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="correct_answer" 
                                           id="correct_a" 
                                           value="a" 
                                           required
                                           <?php echo ($correct_answer ?? '') === 'a' ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="correct_a">
                                        <span class="badge bg-success me-2">A</span>Option A
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="correct_answer" 
                                           id="correct_b" 
                                           value="b" 
                                           required
                                           <?php echo ($correct_answer ?? '') === 'b' ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="correct_b">
                                        <span class="badge bg-success me-2">B</span>Option B
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="correct_answer" 
                                           id="correct_c" 
                                           value="c" 
                                           required
                                           <?php echo ($correct_answer ?? '') === 'c' ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="correct_c">
                                        <span class="badge bg-success me-2">C</span>Option C
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="correct_answer" 
                                           id="correct_d" 
                                           value="d" 
                                           required
                                           <?php echo ($correct_answer ?? '') === 'd' ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="correct_d">
                                        <span class="badge bg-success me-2">D</span>Option D
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            Please select the correct answer.
                        </div>
                        <div class="form-text">
                            Select which option is the correct answer to this question.
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-outline-secondary me-md-2" onclick="clearForm()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Clear Form
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg btn-quiz">
                            <i class="bi bi-plus-circle me-2"></i>Add Question
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-lightbulb me-2"></i>
                    Tips for Creating Good Questions
                </h6>
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li><strong>Be Clear:</strong> Use simple, unambiguous language</li>
                    <li><strong>Avoid Tricks:</strong> Focus on knowledge, not wordplay</li>
                    <li><strong>Balance Options:</strong> Make all options plausible</li>
                    <li><strong>Single Answer:</strong> Ensure only one option is clearly correct</li>
                    <li><strong>Appropriate Length:</strong> Keep options roughly the same length</li>
                    <li><strong>Avoid Absolutes:</strong> Words like "always" or "never" can be giveaways</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Clear form function
function clearForm() {
    if (confirm('Are you sure you want to clear all form data?')) {
        document.querySelector('form').reset();
        document.querySelector('form').classList.remove('was-validated');
    }
}

// Auto-resize textarea
document.getElementById('question_text').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Preview question
function previewQuestion() {
    const questionText = document.getElementById('question_text').value;
    const optionA = document.getElementById('option_a').value;
    const optionB = document.getElementById('option_b').value;
    const optionC = document.getElementById('option_c').value;
    const optionD = document.getElementById('option_d').value;
    const correctAnswer = document.querySelector('input[name="correct_answer"]:checked')?.value;
    
    if (!questionText || !optionA || !optionB || !optionC || !optionD || !correctAnswer) {
        alert('Please fill in all fields to preview the question.');
        return;
    }
    
    // Create preview modal (simplified)
    const preview = `
        <div class="modal fade" id="previewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Question Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card question-card">
                            <div class="card-body">
                                <h5 class="text-white mb-4">${questionText}</h5>
                                <div class="answer-option ${correctAnswer === 'a' ? 'correct' : ''}">
                                    <span class="badge bg-primary me-3">A</span>${optionA}
                                </div>
                                <div class="answer-option ${correctAnswer === 'b' ? 'correct' : ''}">
                                    <span class="badge bg-primary me-3">B</span>${optionB}
                                </div>
                                <div class="answer-option ${correctAnswer === 'c' ? 'correct' : ''}">
                                    <span class="badge bg-primary me-3">C</span>${optionC}
                                </div>
                                <div class="answer-option ${correctAnswer === 'd' ? 'correct' : ''}">
                                    <span class="badge bg-primary me-3">D</span>${optionD}
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-muted">
                            <i class="bi bi-info-circle me-2"></i>
                            The correct answer (${correctAnswer.toUpperCase()}) is highlighted in green.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', preview);
    new bootstrap.Modal(document.getElementById('previewModal')).show();
    
    // Remove modal after it's hidden
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
</script>

<?php include '../includes/footer.php'; ?>