<?php
// Simple diagnostic script for login issues
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$diagnostics = [
    'php_version' => phpversion(),
    'server_time' => date('Y-m-d H:i:s'),
    'post_data' => $_POST ?? [],
    'session_status' => session_status(),
    'files_exist' => [],
    'database_connection' => null,
    'errors' => []
];

// Check if required files exist
$requiredFiles = ['db.php', 'security.php', 'session_validate.php'];
foreach ($requiredFiles as $file) {
    $diagnostics['files_exist'][$file] = file_exists($file);
}

// Test database connection
try {
    include 'db.php';
    $diagnostics['database_connection'] = 'Success';
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    $diagnostics['user_count'] = $result['user_count'] ?? 'Unknown';
    
} catch (Exception $e) {
    $diagnostics['database_connection'] = 'Failed: ' . $e->getMessage();
    $diagnostics['errors'][] = 'Database error: ' . $e->getMessage();
}

// Test security class
try {
    if (file_exists('security.php')) {
        include 'security.php';
        if (class_exists('SecurityManager')) {
            $diagnostics['security_class'] = 'Available';
        } else {
            $diagnostics['security_class'] = 'File exists but class not found';
        }
    } else {
        $diagnostics['security_class'] = 'File not found';
    }
} catch (Exception $e) {
    $diagnostics['security_class'] = 'Error: ' . $e->getMessage();
    $diagnostics['errors'][] = 'Security error: ' . $e->getMessage();
}

// Simulate login validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        try {
            // Test user lookup
            $stmt = $pdo->prepare("SELECT user_id, email, password FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $diagnostics['user_found'] = true;
                $diagnostics['password_verify'] = password_verify($password, $user['password']);
            } else {
                $diagnostics['user_found'] = false;
                $diagnostics['message'] = 'No user found with that email';
            }
            
        } catch (Exception $e) {
            $diagnostics['errors'][] = 'Login test error: ' . $e->getMessage();
        }
    } else {
        $diagnostics['message'] = 'Email and password required for login test';
    }
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>