<?php
/**
 * Quiz Application - Main Landing Page
 * Allows users to enter their name and start the quiz
 */

session_start();
require_once 'config/database.php';

$page_title = "Quiz Application - Start Your Quiz";
$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    }
    // Validate input
    elseif (!validateName($first_name) || !validateName($last_name)) {
        $error_message = 'Please enter valid first and last names (letters and spaces only).';
    }
    else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if user already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE first_name = ? AND last_name = ?");
            $stmt->execute([$first_name, $last_name]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                // User exists, use existing ID
                $user_id = $existing_user['id'];
            } else {
                // Create new user
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name) VALUES (?, ?)");
                $stmt->execute([$first_name, $last_name]);
                $user_id = $db->lastInsertId();
            }
            
            // Store user info in session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['quiz_started'] = false;
            
            // Redirect to quiz
            header('Location: /quiz');
            exit();
            
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
            error_log("User registration error: " . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<div id="alert-container"></div>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <!-- Welcome Card -->
        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h1 class="card-title mb-0">
                    <i class="bi bi-mortarboard-fill me-3"></i>
                    Welcome to Quiz Challenge
                </h1>
                <p class="lead mb-0 mt-2">Test your knowledge and challenge yourself!</p>
            </div>
            <div class="card-body p-5">
                <!-- Features List -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-4"></i>
                            <span>10 Random Questions</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-4"></i>
                            <span>Multiple Choice Format</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-4"></i>
                            <span>Instant Results</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-4"></i>
                            <span>Performance Tracking</span>
                        </div>
                    </div>
                </div>

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

                <!-- Registration Form -->
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label fw-bold">
                                <i class="bi bi-person-fill me-2"></i>First Name
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="first_name" 
                                   name="first_name" 
                                   required 
                                   pattern="[A-Za-z\s]+"
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                   placeholder="Enter your first name">
                            <div class="invalid-feedback">
                                Please provide a valid first name (letters only).
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label fw-bold">
                                <i class="bi bi-person-fill me-2"></i>Last Name
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="last_name" 
                                   name="last_name" 
                                   required 
                                   pattern="[A-Za-z\s]+"
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                   placeholder="Enter your last name">
                            <div class="invalid-feedback">
                                Please provide a valid last name (letters only).
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg btn-quiz">
                            <i class="bi bi-play-circle-fill me-2"></i>
                            Start Quiz Challenge
                        </button>
                    </div>
                </form>

                <!-- Instructions -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                        Quiz Instructions:
                    </h6>
                    <ul class="mb-0 small">
                        <li>You will be presented with 10 randomly selected questions</li>
                        <li>Each question has 4 multiple choice options</li>
                        <li>Select your answer and click "Next" to proceed</li>
                        <li>Your score will be calculated and displayed at the end</li>
                        <li>You can retake the quiz anytime with different questions</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted mb-3">Quiz Statistics</h6>
                <div class="row">
                    <?php
                    try {
                        $database = new Database();
                        $db = $database->getConnection();
                        
                        // Get statistics
                        $stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
                        $total_users = $stmt->fetch()['total_users'];
                        
                        $stmt = $db->query("SELECT COUNT(*) as total_attempts FROM quiz_attempts");
                        $total_attempts = $stmt->fetch()['total_attempts'];
                        
                        $stmt = $db->query("SELECT AVG(percentage) as avg_score FROM quiz_attempts");
                        $avg_score = round($stmt->fetch()['avg_score'] ?? 0, 1);
                        
                        $stmt = $db->query("SELECT COUNT(*) as total_questions FROM questions");
                        $total_questions = $stmt->fetch()['total_questions'];
                    ?>
                    
                    <div class="col-3">
                        <div class="fs-4 fw-bold text-primary"><?php echo $total_users; ?></div>
                        <div class="small text-muted">Users</div>
                    </div>
                    <div class="col-3">
                        <div class="fs-4 fw-bold text-success"><?php echo $total_attempts; ?></div>
                        <div class="small text-muted">Attempts</div>
                    </div>
                    <div class="col-3">
                        <div class="fs-4 fw-bold text-warning"><?php echo $avg_score; ?>%</div>
                        <div class="small text-muted">Avg Score</div>
                    </div>
                    <div class="col-3">
                        <div class="fs-4 fw-bold text-info"><?php echo $total_questions; ?></div>
                        <div class="small text-muted">Questions</div>
                    </div>
                    
                    <?php
                    } catch (Exception $e) {
                        echo '<div class="col-12"><p class="text-muted">Statistics unavailable</p></div>';
                    }
                    ?>
                </div>
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
</script>

<?php include 'includes/footer.php'; ?>