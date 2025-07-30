<?php
/**
 * Manage Questions Page
 * Allows administrators to view, edit, and delete quiz questions
 */

session_start();
require_once '../config/database.php';

$page_title = "Manage Questions - Quiz Management";
$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle question deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $question_id = intval($_GET['id']);
    $csrf_token = $_GET['token'] ?? '';
    
    if (verifyCSRFToken($csrf_token)) {
        try {
            // Check if question is used in any attempts
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM quiz_answers WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $usage_count = $stmt->fetch()['count'];
            
            if ($usage_count > 0) {
                $error_message = "Cannot delete question - it has been used in $usage_count quiz attempts.";
            } else {
                $stmt = $db->prepare("DELETE FROM questions WHERE id = ?");
                if ($stmt->execute([$question_id])) {
                    $success_message = "Question deleted successfully!";
                } else {
                    $error_message = "Failed to delete question.";
                }
            }
        } catch (Exception $e) {
            $error_message = "Error deleting question: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid security token.";
    }
}

// Get all questions with usage statistics
try {
    $stmt = $db->query("
        SELECT q.*, 
               COUNT(qa.id) as usage_count,
               MAX(qa.attempt_date) as last_used
        FROM questions q
        LEFT JOIN quiz_answers qans ON q.id = qans.question_id
        LEFT JOIN quiz_attempts qa ON qans.attempt_id = qa.id
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $questions = $stmt->fetchAll();
    
    // Get total statistics
    $total_questions = count($questions);
    $used_questions = count(array_filter($questions, function($q) { return $q['usage_count'] > 0; }));
    $unused_questions = $total_questions - $used_questions;
    
} catch (Exception $e) {
    $error_message = "Failed to load questions: " . $e->getMessage();
    $questions = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="display-6 mb-0">
                    <i class="bi bi-list-ul me-3"></i>
                    Manage Questions
                </h1>
                <p class="text-muted">View, edit, and manage your quiz questions</p>
            </div>
            <div class="btn-group" role="group">
                <a href="add_question.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add New Question
                </a>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?php echo $total_questions; ?></h3>
                        <p class="text-muted mb-0">Total Questions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?php echo $used_questions; ?></h3>
                        <p class="text-muted mb-0">Used in Quizzes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-warning"><?php echo $unused_questions; ?></h3>
                        <p class="text-muted mb-0">Unused Questions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
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

        <!-- Questions List -->
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-question-circle me-2"></i>
                    All Questions
                </h5>
                <div class="d-flex gap-2">
                    <input type="search" id="searchQuestions" class="form-control form-control-sm" placeholder="Search questions..." style="width: 250px;">
                    <select id="filterUsage" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">All Questions</option>
                        <option value="used">Used Only</option>
                        <option value="unused">Unused Only</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($questions)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-question-circle fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No Questions Found</h5>
                        <p class="text-muted">Start by adding some quiz questions.</p>
                        <a href="add_question.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add Your First Question
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="questionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Question</th>
                                    <th style="width: 120px;">Correct Answer</th>
                                    <th style="width: 100px;">Usage</th>
                                    <th style="width: 120px;">Last Used</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $index => $question): ?>
                                <tr data-usage="<?php echo $question['usage_count'] > 0 ? 'used' : 'unused'; ?>">
                                    <td class="text-muted"><?php echo $question['id']; ?></td>
                                    <td>
                                        <div class="question-preview">
                                            <strong><?php echo htmlspecialchars(substr($question['question_text'], 0, 80)); ?><?php echo strlen($question['question_text']) > 80 ? '...' : ''; ?></strong>
                                            <div class="small text-muted mt-1">
                                                <span class="me-3"><strong>A:</strong> <?php echo htmlspecialchars(substr($question['option_a'], 0, 30)); ?><?php echo strlen($question['option_a']) > 30 ? '...' : ''; ?></span>
                                                <span class="me-3"><strong>B:</strong> <?php echo htmlspecialchars(substr($question['option_b'], 0, 30)); ?><?php echo strlen($question['option_b']) > 30 ? '...' : ''; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success fs-6"><?php echo strtoupper($question['correct_answer']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($question['usage_count'] > 0): ?>
                                            <span class="badge bg-info"><?php echo $question['usage_count']; ?> times</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Unused</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($question['last_used']): ?>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($question['last_used'])); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Never</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-info" onclick="viewQuestion(<?php echo $question['id']; ?>)" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="editQuestion(<?php echo $question['id']; ?>)" title="Edit Question">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($question['usage_count'] == 0): ?>
                                                <button class="btn btn-outline-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)" title="Delete Question">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary" disabled title="Cannot delete - used in quizzes">
                                                    <i class="bi bi-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Question Details Modal -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Question Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="questionDetails">
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

<script>
// Search functionality
document.getElementById('searchQuestions').addEventListener('input', function() {
    filterQuestions();
});

document.getElementById('filterUsage').addEventListener('change', function() {
    filterQuestions();
});

function filterQuestions() {
    const searchTerm = document.getElementById('searchQuestions').value.toLowerCase();
    const usageFilter = document.getElementById('filterUsage').value;
    const rows = document.querySelectorAll('#questionsTable tbody tr');
    
    rows.forEach(row => {
        const questionText = row.querySelector('.question-preview').textContent.toLowerCase();
        const usageType = row.dataset.usage;
        
        const matchesSearch = questionText.includes(searchTerm);
        const matchesUsage = !usageFilter || usageType === usageFilter;
        
        if (matchesSearch && matchesUsage) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function viewQuestion(questionId) {
    const modal = new bootstrap.Modal(document.getElementById('questionModal'));
    const content = document.getElementById('questionDetails');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch question details
    fetch(`question_details.php?id=${questionId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Failed to load question details.
                </div>
            `;
        });
}

function editQuestion(questionId) {
    window.location.href = `edit_question.php?id=${questionId}`;
}

function deleteQuestion(questionId) {
    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        const token = '<?php echo generateCSRFToken(); ?>';
        window.location.href = `?delete=1&id=${questionId}&token=${token}`;
    }
}
</script>

<?php include '../includes/footer.php'; ?>