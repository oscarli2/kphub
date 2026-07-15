<?php
/**
 * KP-HUB Deployment Diagnostic Script
 *
 * This script checks for common deployment issues and provides troubleshooting information
 */

echo "<h1>KP-HUB Deployment Diagnostics</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }</style>";

// Check PHP version
echo "<h2>PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check required extensions
echo "<h2>Required Extensions</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'session'];
foreach ($requiredExtensions as $ext) {
    $status = extension_loaded($ext) ? "<span class='success'>✓ Installed</span>" : "<span class='error'>✗ Missing</span>";
    echo "<p>$ext: $status</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require 'db.php';
    echo "<p class='success'>✓ Database connection successful</p>";

    // Check if migrations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
    $migrationsTableExists = $stmt->rowCount() > 0;
    echo "<p>Migrations table: " . ($migrationsTableExists ? "<span class='success'>✓ Exists</span>" : "<span class='warning'>⚠ Missing</span>") . "</p>";

    if ($migrationsTableExists) {
        // Check applied migrations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM schema_migrations WHERE status = 'success'");
        $appliedCount = $stmt->fetch()['count'];
        echo "<p>Applied migrations: $appliedCount</p>";
    }

    // Check posts table structure
    echo "<h2>Posts Table Structure</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";

    // Check if there are any posts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $postCount = $stmt->fetch()['count'];
    echo "<p>Total posts in database: $postCount</p>";

    // Check recent posts
    if ($postCount > 0) {
        echo "<h2>Recent Posts (Last 5)</h2>";
        $stmt = $pdo->prepare("SELECT post_id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<ul>";
        foreach ($posts as $post) {
            echo "<li>{$post['post_id']}: {$post['title']} ({$post['created_at']})</li>";
        }
        echo "</ul>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";

    // Show current database config (without password)
    echo "<h3>Current Database Configuration</h3>";
    echo "<pre>";
    echo "Host: $host\n";
    echo "Database: $dbname\n";
    echo "Username: $username\n";
    echo "Password: " . (empty($password) ? "(empty)" : "(set)");
    echo "</pre>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$filesToCheck = [
    'db.php' => 'Database configuration',
    'migrate.php' => 'Migration system',
    'deploy.php' => 'Deployment script',
    'get_posts.php' => 'Posts API',
    'index.php' => 'Main application'
];

foreach ($filesToCheck as $file => $description) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file) ? "<span class='success'>✓ Readable</span>" : "<span class='error'>✗ Not readable</span>";
        echo "<p>$file ($description): $readable (permissions: $perms)</p>";
    } else {
        echo "<p class='error'>$file ($description): ✗ File not found</p>";
    }
}

// Check if migrations directory exists
echo "<h2>Migration Files</h2>";
if (is_dir('migrations')) {
    $migrationFiles = glob('migrations/*.php');
    echo "<p>Migrations directory: <span class='success'>✓ Exists</span> (" . count($migrationFiles) . " files)</p>";

    if (!empty($migrationFiles)) {
        echo "<ul>";
        foreach ($migrationFiles as $file) {
            echo "<li>" . basename($file) . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='warning'>Migrations directory: ⚠ Not found</p>";
}

// Recommendations
echo "<h2>Recommendations</h2>";
echo "<ol>";

if (!extension_loaded('pdo_mysql')) {
    echo "<li class='error'>Install or enable the PDO MySQL extension in your PHP configuration</li>";
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    echo "<li class='success'>Database connection is working</li>";

    // Check if migrations were run
    $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
    if ($stmt->rowCount() == 0) {
        echo "<li class='warning'>Run database migrations: Execute <code>php deploy.php</code> to set up the database schema</li>";
    }

    // Check post count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $count = $stmt->fetch()['count'];
    if ($count == 0) {
        echo "<li class='warning'>No posts found in database. You may need to migrate data from your old system</li>";
    }

} catch (PDOException $e) {
    echo "<li class='error'>Fix database configuration in db.php - currently using development settings</li>";
    echo "<li>Uncomment the production database settings in db.php</li>";
}

echo "<li>Test the application by visiting the main page and checking browser developer tools for errors</li>";
echo "<li>Check PHP error logs for any hidden errors</li>";
echo "</ol>";

echo "<hr>";
echo "<p><small>Generated on: " . date('Y-m-d H:i:s') . "</small></p>";
?>