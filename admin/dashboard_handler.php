<?php
/**
 * Dashboard Handler - AJAX endpoint for dashboard operations
 * Handles filtering, user data retrieval, and exports
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$database = new Database();
$db = $database->getConnection();

try {
    switch ($action) {
        case 'filter_users':
            handleUserFiltering($db);
            break;
            
        case 'export':
            handleDataExport($db);
            break;
            
        case 'get_user_stats':
            getUserStats($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Dashboard handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * Handle user filtering based on criteria
 */
function handleUserFiltering($db) {
    $date_from = sanitizeInput($_POST['date_from'] ?? '');
    $date_to = sanitizeInput($_POST['date_to'] ?? '');
    $min_score = intval($_POST['min_score'] ?? 0);
    $min_attempts = intval($_POST['min_attempts'] ?? 0);
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if ($date_from) {
        $where_conditions[] = "qa.attempt_date >= ?";
        $params[] = $date_from . ' 00:00:00';
    }
    
    if ($date_to) {
        $where_conditions[] = "qa.attempt_date <= ?";
        $params[] = $date_to . ' 23:59:59';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get filtered users
    $sql = "
        SELECT u.id, u.first_name, u.last_name, u.created_at,
               COUNT(qa.id) as total_attempts,
               MAX(qa.percentage) as best_score,
               AVG(qa.percentage) as avg_score,
               MAX(qa.attempt_date) as last_attempt
        FROM users u 
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id 
        $where_clause
        GROUP BY u.id 
    ";
    
    // Apply additional filters
    $having_conditions = [];
    if ($min_score > 0) {
        $having_conditions[] = "best_score >= $min_score";
    }
    if ($min_attempts > 0) {
        $having_conditions[] = "total_attempts >= $min_attempts";
    }
    
    if (!empty($having_conditions)) {
        $sql .= ' HAVING ' . implode(' AND ', $having_conditions);
    }
    
    $sql .= ' ORDER BY u.created_at DESC LIMIT 50';
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get updated statistics
    $stats = getFilteredStats($db, $date_from, $date_to, $min_score, $min_attempts);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'stats' => $stats
    ]);
}

/**
 * Get filtered statistics
 */
function getFilteredStats($db, $date_from, $date_to, $min_score, $min_attempts) {
    $where_conditions = [];
    $params = [];
    
    if ($date_from) {
        $where_conditions[] = "attempt_date >= ?";
        $params[] = $date_from . ' 00:00:00';
    }
    
    if ($date_to) {
        $where_conditions[] = "attempt_date <= ?";
        $params[] = $date_to . ' 23:59:59';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Total users
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as total_users FROM quiz_attempts $where_clause");
    $total_users = $stmt->fetch()['total_users'] ?? 0;
    
    // Total attempts
    $stmt = $db->prepare("SELECT COUNT(*) as total_attempts FROM quiz_attempts $where_clause");
    $stmt->execute($params);
    $total_attempts = $stmt->fetch()['total_attempts'] ?? 0;
    
    // Average score
    $stmt = $db->prepare("SELECT AVG(percentage) as avg_score FROM quiz_attempts $where_clause");
    $stmt->execute($params);
    $avg_score = round($stmt->fetch()['avg_score'] ?? 0, 1);
    
    // Questions count
    $stmt = $db->query("SELECT COUNT(*) as questions_count FROM questions");
    $questions_count = $stmt->fetch()['questions_count'] ?? 0;
    
    return [
        'total_users' => $total_users,
        'total_attempts' => $total_attempts,
        'average_score' => $avg_score,
        'questions_count' => $questions_count
    ];
}

/**
 * Handle data export
 */
function handleDataExport($db) {
    $format = sanitizeInput($_GET['export'] ?? 'csv');
    $date_from = sanitizeInput($_GET['date_from'] ?? '');
    $date_to = sanitizeInput($_GET['date_to'] ?? '');
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($date_from) {
        $where_conditions[] = "qa.attempt_date >= ?";
        $params[] = $date_from . ' 00:00:00';
    }
    
    if ($date_to) {
        $where_conditions[] = "qa.attempt_date <= ?";
        $params[] = $date_to . ' 23:59:59';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $sql = "
        SELECT u.first_name, u.last_name, u.created_at,
               qa.score, qa.total_questions, qa.percentage, qa.attempt_date
        FROM users u 
        INNER JOIN quiz_attempts qa ON u.id = qa.user_id 
        $where_clause
        ORDER BY qa.attempt_date DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    if ($format === 'csv') {
        exportToCSV($data);
    } else {
        exportToJSON($data);
    }
}

/**
 * Export data to CSV
 */
function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="quiz_results_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'First Name', 'Last Name', 'Registration Date', 
        'Score', 'Total Questions', 'Percentage', 'Attempt Date'
    ]);
    
    // CSV data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['first_name'],
            $row['last_name'],
            $row['created_at'],
            $row['score'],
            $row['total_questions'],
            $row['percentage'] . '%',
            $row['attempt_date']
        ]);
    }
    
    fclose($output);
    exit();
}

/**
 * Export data to JSON
 */
function exportToJSON($data) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="quiz_results_' . date('Y-m-d') . '.json"');
    
    echo json_encode([
        'export_date' => date('Y-m-d H:i:s'),
        'total_records' => count($data),
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit();
}

/**
 * Get user statistics
 */
function getUserStats($db) {
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Get user info
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    // Get user attempts
    $stmt = $db->prepare("
        SELECT * FROM quiz_attempts 
        WHERE user_id = ? 
        ORDER BY attempt_date DESC
    ");
    $stmt->execute([$user_id]);
    $attempts = $stmt->fetchAll();
    
    // Calculate statistics
    $stats = [
        'total_attempts' => count($attempts),
        'best_score' => 0,
        'worst_score' => 100,
        'average_score' => 0,
        'improvement_trend' => 0
    ];
    
    if (!empty($attempts)) {
        $scores = array_column($attempts, 'percentage');
        $stats['best_score'] = max($scores);
        $stats['worst_score'] = min($scores);
        $stats['average_score'] = round(array_sum($scores) / count($scores), 1);
        
        // Calculate improvement trend (last 3 vs first 3 attempts)
        if (count($attempts) >= 6) {
            $recent_avg = array_sum(array_slice($scores, 0, 3)) / 3;
            $early_avg = array_sum(array_slice($scores, -3, 3)) / 3;
            $stats['improvement_trend'] = round($recent_avg - $early_avg, 1);
        }
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'attempts' => $attempts,
        'stats' => $stats
    ]);
}
?>