<?php
/**
 * Admin Dashboard
 * Displays user management, analytics, and quiz statistics
 */

session_start();
require_once '../config/database.php';

$page_title = "Admin Dashboard - Quiz Management";
$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
try {
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
    
    // Total attempts
    $stmt = $db->query("SELECT COUNT(*) as total_attempts FROM quiz_attempts");
    $total_attempts = $stmt->fetch()['total_attempts'];
    
    // Average score
    $stmt = $db->query("SELECT AVG(percentage) as avg_score FROM quiz_attempts");
    $avg_score = round($stmt->fetch()['avg_score'] ?? 0, 1);
    
    // Total questions
    $stmt = $db->query("SELECT COUNT(*) as total_questions FROM questions");
    $total_questions = $stmt->fetch()['total_questions'];
    
    // Recent attempts (last 7 days)
    $stmt = $db->query("
        SELECT COUNT(*) as recent_attempts 
        FROM quiz_attempts 
        WHERE attempt_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $recent_attempts = $stmt->fetch()['recent_attempts'];
    
    // Top performers
    $stmt = $db->query("
        SELECT u.first_name, u.last_name, 
               MAX(qa.percentage) as best_score,
               COUNT(qa.id) as total_attempts,
               AVG(qa.percentage) as avg_score
        FROM users u 
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id 
        GROUP BY u.id 
        HAVING total_attempts > 0
        ORDER BY best_score DESC, avg_score DESC 
        LIMIT 10
    ");
    $top_performers = $stmt->fetchAll();
    
    // Recent users with performance data
    $stmt = $db->query("
        SELECT u.id, u.first_name, u.last_name, u.created_at,
               COUNT(qa.id) as total_attempts,
               MAX(qa.percentage) as best_score,
               AVG(qa.percentage) as avg_score,
               MAX(qa.attempt_date) as last_attempt
        FROM users u 
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC 
        LIMIT 20
    ");
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Failed to load dashboard data: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div id="dashboard-container">
    <div id="alert-container"></div>
    
    <!-- Dashboard Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-6">
                <i class="bi bi-speedometer2 me-3"></i>
                Admin Dashboard
            </h1>
            <p class="lead text-muted">Manage users, questions, and analyze quiz performance</p>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <a href="add_question.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Question
                </a>
                <a href="manage_questions.php" class="btn btn-outline-primary">
                    <i class="bi bi-list-ul me-2"></i>Manage Questions
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 id="total-users" class="fw-bold mb-1"><?php echo $total_users; ?></h3>
                            <p class="text-white-50 mb-0">Total Users</p>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 id="total-attempts" class="fw-bold mb-1"><?php echo $total_attempts; ?></h3>
                            <p class="text-white-50 mb-0">Quiz Attempts</p>
                        </div>
                        <i class="bi bi-clipboard-check-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white;">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 id="average-score" class="fw-bold mb-1"><?php echo $avg_score; ?>%</h3>
                            <p class="text-white-50 mb-0">Average Score</p>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); color: white;">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 id="questions-count" class="fw-bold mb-1"><?php echo $total_questions; ?></h3>
                            <p class="text-white-50 mb-0">Questions</p>
                        </div>
                        <i class="bi bi-question-circle-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Analytics -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>
                        Filter & Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <form id="filter-form" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control filter-select">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control filter-select">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Score</label>
                            <select name="min_score" class="form-select filter-select">
                                <option value="">All Scores</option>
                                <option value="90">90%+</option>
                                <option value="80">80%+</option>
                                <option value="70">70%+</option>
                                <option value="60">60%+</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Attempts</label>
                            <select name="min_attempts" class="form-select filter-select">
                                <option value="">All Users</option>
                                <option value="1">1+ Attempts</option>
                                <option value="3">3+ Attempts</option>
                                <option value="5">5+ Attempts</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-week me-2"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo $recent_attempts; ?></h3>
                    <p class="text-muted mb-0">Quiz attempts in last 7 days</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table and Top Performers -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>
                        User Management
                    </h5>
                    <span class="badge bg-primary"><?php echo count($users); ?> users</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="users-table" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Attempts</th>
                                    <th>Best Score</th>
                                    <th>Avg Score</th>
                                    <th>Last Attempt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <small class="text-muted">Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $user['total_attempts'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($user['best_score']): ?>
                                            <span class="badge bg-<?php echo $user['best_score'] >= 80 ? 'success' : ($user['best_score'] >= 60 ? 'warning' : 'danger'); ?>">
                                                <?php echo round($user['best_score'], 1); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['avg_score']): ?>
                                            <?php echo round($user['avg_score'], 1); ?>%
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['last_attempt']): ?>
                                            <small><?php echo date('M j, Y', strtotime($user['last_attempt'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy me-2"></i>
                        Top Performers
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_performers)): ?>
                        <p class="text-muted text-center">No quiz attempts yet</p>
                    <?php else: ?>
                        <?php foreach ($top_performers as $index => $performer): ?>
                        <div class="d-flex align-items-center mb-3 performance-card p-3 rounded">
                            <div class="rank-badge me-3">
                                <?php if ($index < 3): ?>
                                    <i class="bi bi-award-fill fs-4 text-<?php echo ['warning', 'secondary', 'warning'][$index]; ?>"></i>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($performer['first_name'] . ' ' . $performer['last_name']); ?></div>
                                <small class="text-muted"><?php echo $performer['total_attempts']; ?> attempts</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success"><?php echo round($performer['best_score'], 1); ?>%</div>
                                <small class="text-muted">avg: <?php echo round($performer['avg_score'], 1); ?>%</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 0.875rem;
}

.rank-badge {
    width: 40px;
    text-align: center;
}
</style>

<script>
// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Set current date as default for date filters
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    document.querySelector('input[name="date_to"]').value = today;
    document.querySelector('input[name="date_from"]').value = weekAgo;
});

function viewUserDetails(userId) {
    window.location.href = `user_details.php?id=${userId}`;
}

// Export functionality
function exportData(format) {
    const params = new URLSearchParams();
    document.querySelectorAll('.filter-select').forEach(select => {
        if (select.value) {
            params.append(select.name, select.value);
        }
    });
    params.append('export', format);
    
    window.open(`dashboard_handler.php?${params.toString()}`, '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>