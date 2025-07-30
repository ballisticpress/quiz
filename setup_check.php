<?php
/**
 * Setup Check Script
 * Verifies that the quiz application is properly configured and ready to use
 */

$checks = [];
$overall_status = true;

// Check PHP version
$php_version = PHP_VERSION;
$php_ok = version_compare($php_version, '7.4.0', '>=');
$checks['PHP Version'] = [
    'status' => $php_ok,
    'message' => $php_ok ? "PHP $php_version (✓)" : "PHP $php_version - Requires 7.4+ (✗)",
    'required' => true
];
if (!$php_ok) $overall_status = false;

// Check if config file exists
$config_exists = file_exists('config/database.php');
$checks['Configuration File'] = [
    'status' => $config_exists,
    'message' => $config_exists ? 'config/database.php exists (✓)' : 'config/database.php missing (✗)',
    'required' => true
];
if (!$config_exists) $overall_status = false;

// Check database connection
$db_connected = false;
$db_message = '';
if ($config_exists) {
    try {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        if ($db) {
            $db_connected = true;
            $db_message = 'Database connection successful (✓)';
        } else {
            $db_message = 'Database connection failed (✗)';
        }
    } catch (Exception $e) {
        $db_message = 'Database connection error: ' . $e->getMessage() . ' (✗)';
    }
} else {
    $db_message = 'Cannot test - config file missing (✗)';
}

$checks['Database Connection'] = [
    'status' => $db_connected,
    'message' => $db_message,
    'required' => true
];
if (!$db_connected) $overall_status = false;

// Check if tables exist
$tables_exist = false;
$tables_message = '';
if ($db_connected) {
    try {
        $required_tables = ['users', 'questions', 'quiz_attempts', 'quiz_answers'];
        $existing_tables = [];
        
        foreach ($required_tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $existing_tables[] = $table;
            }
        }
        
        if (count($existing_tables) === count($required_tables)) {
            $tables_exist = true;
            $tables_message = 'All required tables exist (✓)';
        } else {
            $missing = array_diff($required_tables, $existing_tables);
            $tables_message = 'Missing tables: ' . implode(', ', $missing) . ' (✗)';
        }
    } catch (Exception $e) {
        $tables_message = 'Error checking tables: ' . $e->getMessage() . ' (✗)';
    }
} else {
    $tables_message = 'Cannot check - no database connection (✗)';
}

$checks['Database Tables'] = [
    'status' => $tables_exist,
    'message' => $tables_message,
    'required' => true
];
if (!$tables_exist) $overall_status = false;

// Check if sample questions exist
$questions_exist = false;
$questions_message = '';
if ($tables_exist) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM questions");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $questions_exist = true;
            $questions_message = "$count questions in database (✓)";
        } else {
            $questions_message = 'No questions in database - run the SQL file (⚠)';
        }
    } catch (Exception $e) {
        $questions_message = 'Error checking questions: ' . $e->getMessage() . ' (✗)';
    }
} else {
    $questions_message = 'Cannot check - tables missing (✗)';
}

$checks['Sample Questions'] = [
    'status' => $questions_exist,
    'message' => $questions_message,
    'required' => false
];

// Check directory permissions
$directories = ['assets/css', 'assets/js', 'admin', 'config', 'includes'];
$permissions_ok = true;
$permission_messages = [];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            $permission_messages[] = "$dir readable (✓)";
        } else {
            $permission_messages[] = "$dir not readable (✗)";
            $permissions_ok = false;
        }
    } else {
        $permission_messages[] = "$dir missing (✗)";
        $permissions_ok = false;
    }
}

$checks['Directory Permissions'] = [
    'status' => $permissions_ok,
    'message' => implode(', ', $permission_messages),
    'required' => true
];
if (!$permissions_ok) $overall_status = false;

// Check required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'session'];
$extensions_ok = true;
$extension_messages = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $extension_messages[] = "$ext loaded (✓)";
    } else {
        $extension_messages[] = "$ext missing (✗)";
        $extensions_ok = false;
    }
}

$checks['PHP Extensions'] = [
    'status' => $extensions_ok,
    'message' => implode(', ', $extension_messages),
    'required' => true
];
if (!$extensions_ok) $overall_status = false;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Application - Setup Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header <?php echo $overall_status ? 'bg-success' : 'bg-danger'; ?> text-white text-center py-4">
                        <h1 class="card-title mb-0">
                            <i class="bi <?php echo $overall_status ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-3"></i>
                            Setup Check
                        </h1>
                        <p class="lead mb-0 mt-2">
                            <?php echo $overall_status ? 'Application is ready to use!' : 'Setup issues detected'; ?>
                        </p>
                    </div>
                    <div class="card-body p-4">
                        <?php foreach ($checks as $check_name => $check_data): ?>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo $check_name; ?></h6>
                                <p class="mb-0 text-muted"><?php echo $check_data['message']; ?></p>
                            </div>
                            <div>
                                <?php if ($check_data['status']): ?>
                                    <span class="badge bg-success fs-6">
                                        <i class="bi bi-check-circle-fill"></i> OK
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-<?php echo $check_data['required'] ? 'danger' : 'warning'; ?> fs-6">
                                        <i class="bi bi-<?php echo $check_data['required'] ? 'x-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
                                        <?php echo $check_data['required'] ? 'ERROR' : 'WARNING'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-4">
                            <?php if ($overall_status): ?>
                                <div class="alert alert-success">
                                    <h5 class="alert-heading">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        Setup Complete!
                                    </h5>
                                    <p class="mb-3">Your quiz application is properly configured and ready to use.</p>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                        <a href="index.php" class="btn btn-success btn-lg me-md-2">
                                            <i class="bi bi-play-circle-fill me-2"></i>Start Using Quiz App
                                        </a>
                                        <a href="admin/dashboard.php" class="btn btn-outline-success btn-lg">
                                            <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Setup Issues Detected
                                    </h5>
                                    <p class="mb-3">Please resolve the issues above before using the application.</p>
                                    <h6>Common Solutions:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Database Connection:</strong> Check your credentials in config/database.php</li>
                                        <li><strong>Missing Tables:</strong> Import sql/database_schema.sql into your database</li>
                                        <li><strong>PHP Extensions:</strong> Install required PHP extensions (pdo, pdo_mysql)</li>
                                        <li><strong>Permissions:</strong> Ensure web server can read application files</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <button onclick="location.reload()" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Re-check Setup
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        For detailed setup instructions, see the README.md file
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>