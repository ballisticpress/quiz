<?php
/**
 * User Details Page
 * Displays detailed information about a specific user's quiz performance
 */

session_start();
require_once '../config/database.php';

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: dashboard.php');
    exit();
}

$page_title = "User Details - Quiz Management";
$database = new Database();
$db = $database->getConnection();

try {
    // Get user information
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: dashboard.php?error=user_not_found');
        exit();
    }
    
    // Get user attempts with detailed information
    $stmt = $db->prepare("
        SELECT qa.*, 
               COUNT(qans.id) as answered_questions,
               SUM(CASE WHEN qans.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
        FROM quiz_attempts qa
        LEFT JOIN quiz_answers qans ON qa.id = qans.attempt_id
        WHERE qa.user_id = ?
        GROUP BY qa.id
        ORDER BY qa.attempt_date DESC
    ");
    $stmt->execute([$user_id]);
    $attempts = $stmt->fetchAll();
    
    // Calculate user statistics
    $stats = [
        'total_attempts' => count($attempts),
        'best_score' => 0,
        'worst_score' => 100,
        'average_score' => 0,
        'total_questions_answered' => 0,
        'total_correct_answers' => 0,
        'improvement_trend' => 0
    ];
    
    if (!empty($attempts)) {
        $scores = array_column($attempts, 'percentage');
        $stats['best_score'] = max($scores);
        $stats['worst_score'] = min($scores);
        $stats['average_score'] = round(array_sum($scores) / count($scores), 1);
        $stats['total_questions_answered'] = array_sum(array_column($attempts, 'answered_questions'));
        $stats['total_correct_answers'] = array_sum(array_column($attempts, 'correct_answers'));
        
        // Calculate improvement trend
        if (count($attempts) >= 2) {
            $recent_scores = array_slice($scores, 0, min(3, count($scores)));
            $early_scores = array_slice($scores, -min(3, count($scores)));
            $recent_avg = array_sum($recent_scores) / count($recent_scores);
            $early_avg = array_sum($early_scores) / count($early_scores);
            $stats['improvement_trend'] = round($recent_avg - $early_avg, 1);
        }
    }
    
} catch (Exception $e) {
    $error_message = "Failed to load user data: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="row">
    <!-- User Profile Card -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                <p class="text-muted mb-3">Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                
                <!-- Quick Stats -->
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-primary"><?php echo $stats['total_attempts']; ?></div>
                        <div class="small text-muted">Attempts</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-success"><?php echo $stats['best_score']; ?>%</div>
                        <div class="small text-muted">Best Score</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-info"><?php echo $stats['average_score']; ?>%</div>
                        <div class="small text-muted">Average</div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Performance Summary -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Performance Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Questions Answered</span>
                        <span class="small fw-bold"><?php echo $stats['total_questions_answered']; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Correct Answers</span>
                        <span class="small fw-bold"><?php echo $stats['total_correct_answers']; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <?php 
                        $accuracy = $stats['total_questions_answered'] > 0 ? 
                            ($stats['total_correct_answers'] / $stats['total_questions_answered']) * 100 : 0;
                        ?>
                        <div class="progress-bar bg-success" style="width: <?php echo $accuracy; ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo round($accuracy, 1); ?>% accuracy</small>
                </div>
                
                <div class="mb-0">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Improvement Trend</span>
                        <span class="small fw-bold <?php echo $stats['improvement_trend'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $stats['improvement_trend'] > 0 ? '+' : ''; ?><?php echo $stats['improvement_trend']; ?>%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar <?php echo $stats['improvement_trend'] >= 0 ? 'bg-success' : 'bg-danger'; ?>" 
                             style="width: <?php echo min(100, abs($stats['improvement_trend']) * 10); ?>%"></div>
                    </div>
                    <small class="text-muted">
                        <?php if ($stats['improvement_trend'] > 0): ?>
                            <i class="bi bi-arrow-up text-success"></i> Improving
                        <?php elseif ($stats['improvement_trend'] < 0): ?>
                            <i class="bi bi-arrow-down text-danger"></i> Declining
                        <?php else: ?>
                            <i class="bi bi-arrow-right text-muted"></i> Stable
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quiz Attempts History -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Quiz Attempts History
                </h5>
                <span class="badge bg-primary"><?php echo count($attempts); ?> attempts</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($attempts)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No Quiz Attempts</h5>
                        <p class="text-muted">This user hasn't taken any quizzes yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Questions</th>
                                    <th>Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attempts as $index => $attempt): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo date('M j, Y', strtotime($attempt['attempt_date'])); ?></div>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($attempt['attempt_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $attempt['percentage'] >= 90 ? 'success' : 
                                                ($attempt['percentage'] >= 80 ? 'info' : 
                                                ($attempt['percentage'] >= 70 ? 'warning' : 
                                                ($attempt['percentage'] >= 60 ? 'primary' : 'danger'))); 
                                        ?> fs-6">
                                            <?php echo $attempt['percentage']; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $attempt['answered_questions']; ?> answered<br>
                                            <?php echo $attempt['correct_answers']; ?> correct
                                        </small>
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
                                        <small class="text-muted">
                                            <?php 
                                            if ($attempt['percentage'] >= 90) echo 'Excellent';
                                            elseif ($attempt['percentage'] >= 80) echo 'Great';
                                            elseif ($attempt['percentage'] >= 70) echo 'Good';
                                            elseif ($attempt['percentage'] >= 60) echo 'Fair';
                                            else echo 'Needs Improvement';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewAttemptDetails(<?php echo $attempt['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Performance Chart -->
        <?php if (count($attempts) > 1): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart me-2"></i>
                    Performance Trend
                </h6>
            </div>
            <div class="card-body">
                <canvas id="performanceChart" height="100"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Attempt Details Modal -->
<div class="modal fade" id="attemptDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attempt Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attemptDetailsContent">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (count($attempts) > 1): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance trend chart
const ctx = document.getElementById('performanceChart').getContext('2d');
const attempts = <?php echo json_encode(array_reverse($attempts)); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: attempts.map((attempt, index) => `Attempt ${index + 1}`),
        datasets: [{
            label: 'Score Percentage',
            data: attempts.map(attempt => attempt.percentage),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Score: ' + context.parsed.y + '%';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<script>
function viewAttemptDetails(attemptId) {
    const modal = new bootstrap.Modal(document.getElementById('attemptDetailsModal'));
    const content = document.getElementById('attemptDetailsContent');
    
    // Show loading spinner
    content.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch attempt details
    fetch(`attempt_details.php?id=${attemptId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Failed to load attempt details.
                </div>
            `;
        });
}
</script>

<?php include '../includes/footer.php'; ?>