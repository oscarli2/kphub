<?php
/**
 * Web-based Deployment Runner
 *
 * Access this file via web browser to run deployment on servers without SSH access
 * IMPORTANT: Delete this file after deployment for security!
 */

echo "<h1>KP-HUB Deployment Runner</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }</style>";

// Security check - only allow access from specific IP or with password
$allowedIPs = ['127.0.0.1', '::1']; // Add your IP addresses here
$deploymentPassword = 'kphub_deploy_2025'; // Change this password!

$userIP = $_SERVER['REMOTE_ADDR'] ?? '';
$providedPassword = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $providedPassword === $deploymentPassword) {
    // Run deployment
    echo "<h2>Running Deployment...</h2>";

    // Capture output
    ob_start();

    try {
        // Include and run deployment script
        require 'deploy.php';
    } catch (Exception $e) {
        echo "<p class='error'>Deployment failed: " . $e->getMessage() . "</p>";
    }

    $output = ob_get_clean();
    echo "<pre>$output</pre>";

    echo "<hr>";
    echo "<p class='success'>Deployment process completed. Check the output above for any issues.</p>";
    echo "<p><strong>IMPORTANT:</strong> Delete this file (<code>web_deploy.php</code>) from your server for security!</p>";

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p class='error'>Invalid password. Access denied.</p>";
}

// Show form
echo "<h2>Deployment Access</h2>";
echo "<p>This tool allows you to run database migrations and deployment checks.</p>";
echo "<p><strong>Security Notice:</strong> This file should be deleted after deployment!</p>";

echo "<form method='POST'>";
echo "<label for='password'>Deployment Password:</label><br>";
echo "<input type='password' name='password' id='password' required><br><br>";
echo "<button type='submit'>Run Deployment</button>";
echo "</form>";

echo "<hr>";
echo "<h2>Current Status</h2>";

// Quick status check
try {
    require 'db.php';
    echo "<p class='success'>✓ Database connection successful</p>";

    // Check migrations
    $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM schema_migrations WHERE status = 'success'");
        $count = $stmt->fetch()['count'];
        echo "<p>Applied migrations: $count</p>";
    } else {
        echo "<p class='warning'>⚠ Migrations table not found - deployment needed</p>";
    }

    // Check posts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $count = $stmt->fetch()['count'];
    echo "<p>Total posts: $count</p>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Check your database configuration in db.php</p>";
}

echo "<hr>";
echo "<p><small>KP-HUB Deployment Runner - " . date('Y-m-d H:i:s') . "</small></p>";
?>