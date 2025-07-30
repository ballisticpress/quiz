<?php
/**
 * Quiz Results Page
 * Displays quiz results with detailed breakdown and options to retake
 */

session_start();
require_once 'config/database.php';

// Check if user has completed a quiz
if (!isset($_SESSION['quiz_results']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = "Quiz Results - Your Performance";
$results = $_SESSION['quiz_results'];
$user_name = $_SESSION['user_name'];

// Determine performance level and styling
$percentage = $results['percentage'];
$performance_class = '';
$performance_text = '';
$performance_icon = '';

if ($percentage >= 90) {
    $performance_class = 'text-success';
    $performance_text = 'Excellent!';
    $performance_icon = 'bi-trophy-fill';
} elseif ($percentage >= 80) {
    $performance_class = 'text-info';
    $performance_text = 'Great Job!';
    $performance_icon = 'bi-award-fill';
} elseif ($percentage >= 70) {
    $performance_class = 'text-warning';
    $performance_text = 'Good Work!';
    $performance_icon = 'bi-star-fill';
} elseif ($percentage >= 60) {
    $performance_class = 'text-primary';
    $performance_text = 'Not Bad!';
    $performance_icon = 'bi-hand-thumbs-up-fill';
} else {
    $performance_class = 'text-danger';
    $performance_text = 'Keep Trying!';
    $performance_icon = 'bi-arrow-repeat';
}

// Format time taken
$minutes = floor($results['time_taken'] / 60);
$seconds = $results['time_taken'] % 60;
$time_formatted = sprintf('%02d:%02d', $minutes, $seconds);

include 'includes/header.php';
?>

<div class="results-container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <!-- Main Results Card -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h1 class="card-title mb-0">
                        <i class="bi <?php echo $performance_icon; ?> me-3"></i>
                        Quiz Completed!
                    </h1>
                    <p class="lead mb-0 mt-2">Here are your results, <?php echo htmlspecialchars($user_name); ?></p>
                </div>
                <div class="card-body p-5 text-center">
                    <!-- Score Display -->
                    <div class="score-display <?php echo $performance_class; ?>">
                        <?php echo $results['correct_answers']; ?>/<?php echo $results['total_questions']; ?>
                    </div>
                    <div class="score-percentage <?php echo $performance_class; ?>">
                        <?php echo $percentage; ?>%
                    </div>
                    <h3 class="<?php echo $performance_class; ?> mb-4">
                        <?php echo $performance_text; ?>
                    </h3>

                    <!-- Performance Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card bg-primary text-white p-3 rounded">
                                <div class="fs-4 fw-bold"><?php echo $results['correct_answers']; ?></div>
                                <div class="small">Correct</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card bg-danger text-white p-3 rounded">
                                <div class="fs-4 fw-bold"><?php echo $results['total_questions'] - $results['correct_answers']; ?></div>
                                <div class="small">Incorrect</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card bg-success text-white p-3 rounded">
                                <div class="fs-4 fw-bold"><?php echo $percentage; ?>%</div>
                                <div class="small">Score</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card bg-info text-white p-3 rounded">
                                <div class="fs-4 fw-bold"><?php echo $time_formatted; ?></div>
                                <div class="small">Time</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="/quiz?new=1" class="btn btn-primary btn-lg btn-quiz">
                            <i class="bi bi-arrow-repeat me-2"></i>Take Another Quiz
                        </a>
                        <button class="btn btn-outline-primary btn-lg" onclick="toggleDetails()">
                            <i class="bi bi-list-ul me-2"></i>View Details
                        </button>
                        <a href="/admin" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Detailed Results (Initially Hidden) -->
            <div id="detailed-results" class="card border-0 shadow-sm" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-data me-2"></i>
                        Detailed Question Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($results['question_results'] as $index => $question): ?>
                        <div class="question-review mb-4 p-3 border rounded <?php echo $question['is_correct'] ? 'border-success bg-light-success' : 'border-danger bg-light-danger'; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="fw-bold mb-0">
                                    Question <?php echo $index + 1; ?>
                                    <?php if ($question['is_correct']): ?>
                                        <span class="badge bg-success ms-2">
                                            <i class="bi bi-check-circle-fill"></i> Correct
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger ms-2">
                                            <i class="bi bi-x-circle-fill"></i> Incorrect
                                        </span>
                                    <?php endif; ?>
                                </h6>
                            </div>
                            
                            <p class="mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            
                            <div class="row">
                                <?php 
                                $options = ['a', 'b', 'c', 'd'];
                                foreach ($options as $option): 
                                    $is_correct = ($option === $question['correct_answer']);
                                    $is_selected = ($option === $question['user_answer']);
                                    $class = '';
                                    
                                    if ($is_correct) {
                                        $class = 'bg-success text-white';
                                    } elseif ($is_selected && !$is_correct) {
                                        $class = 'bg-danger text-white';
                                    } elseif ($is_selected) {
                                        $class = 'bg-primary text-white';
                                    }
                                ?>
                                <div class="col-md-6 mb-2">
                                    <div class="p-2 rounded border <?php echo $class; ?>">
                                        <strong><?php echo strtoupper($option); ?>:</strong> 
                                        <?php echo htmlspecialchars($question['options'][$option]); ?>
                                        <?php if ($is_selected): ?>
                                            <i class="bi bi-arrow-left float-end"></i>
                                        <?php endif; ?>
                                        <?php if ($is_correct): ?>
                                            <i class="bi bi-check-circle-fill float-end"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Performance History -->
            <?php
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                $stmt = $db->prepare("
                    SELECT percentage, attempt_date, score, total_questions 
                    FROM quiz_attempts 
                    WHERE user_id = ? 
                    ORDER BY attempt_date DESC 
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recent_attempts = $stmt->fetchAll();
                
                if (count($recent_attempts) > 1):
            ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Recent Performance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($attempt['attempt_date'])); ?></td>
                                    <td><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $attempt['percentage'] >= 80 ? 'success' : 
                                                ($attempt['percentage'] >= 60 ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $attempt['percentage']; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-<?php 
                                                echo $attempt['percentage'] >= 80 ? 'success' : 
                                                    ($attempt['percentage'] >= 60 ? 'warning' : 'danger'); 
                                            ?>" 
                                                 style="width: <?php echo $attempt['percentage']; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php 
                endif;
            } catch (Exception $e) {
                // Silently handle database errors
            }
            ?>
        </div>
    </div>
</div>

<script>
function toggleDetails() {
    const detailsDiv = document.getElementById('detailed-results');
    const button = event.target;
    
    if (detailsDiv.style.display === 'none') {
        detailsDiv.style.display = 'block';
        button.innerHTML = '<i class="bi bi-eye-slash me-2"></i>Hide Details';
        detailsDiv.scrollIntoView({ behavior: 'smooth' });
    } else {
        detailsDiv.style.display = 'none';
        button.innerHTML = '<i class="bi bi-list-ul me-2"></i>View Details';
    }
}

// Confetti effect for high scores
<?php if ($percentage >= 80): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Simple confetti effect
    setTimeout(function() {
        for (let i = 0; i < 50; i++) {
            createConfetti();
        }
    }, 500);
});

function createConfetti() {
    const confetti = document.createElement('div');
    confetti.style.position = 'fixed';
    confetti.style.left = Math.random() * 100 + 'vw';
    confetti.style.top = '-10px';
    confetti.style.width = '10px';
    confetti.style.height = '10px';
    confetti.style.backgroundColor = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'][Math.floor(Math.random() * 5)];
    confetti.style.zIndex = '9999';
    confetti.style.borderRadius = '50%';
    confetti.style.pointerEvents = 'none';
    
    document.body.appendChild(confetti);
    
    let fall = 0;
    const fallInterval = setInterval(function() {
        fall += 5;
        confetti.style.top = fall + 'px';
        
        if (fall > window.innerHeight) {
            clearInterval(fallInterval);
            document.body.removeChild(confetti);
        }
    }, 50);
}
<?php endif; ?>

// Clear quiz session data to prevent issues
<?php 
// Clear quiz session data after displaying results
unset($_SESSION['quiz_questions']);
unset($_SESSION['quiz_answers']);
unset($_SESSION['current_question']);
unset($_SESSION['quiz_started']);
unset($_SESSION['quiz_start_time']);
?>
</script>

<style>
.bg-light-success { background-color: rgba(40, 167, 69, 0.1) !important; }
.bg-light-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
</style>

<?php include 'includes/footer.php'; ?>