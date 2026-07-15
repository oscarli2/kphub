<?php
/**
 * Data Migration Helper
 *
 * This script helps migrate data between different database schemas
 * Useful when deploying new versions with schema changes
 */

require 'db.php';

echo "<h1>KP-HUB Data Migration Helper</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .warning { color: orange; }</style>";

try {
    // Check current posts table structure
    echo "<h2>Current Posts Table Analysis</h2>";

    $stmt = $pdo->query("SHOW COLUMNS FROM posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " .
             ($column['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') .
             ($column['Default'] ? " DEFAULT '{$column['Default']}'" : '') . "</li>";
    }
    echo "</ul>";

    // Sample data check
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
    $total = $stmt->fetch()['total'];
    echo "<p>Total posts: <strong>$total</strong></p>";

    if ($total > 0) {
        // Check for posts without titles
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE title IS NULL OR title = ''");
        $stmt->execute();
        $untitled = $stmt->fetch()['count'];

        if ($untitled > 0) {
            echo "<p class='warning'>⚠ $untitled posts found without titles</p>";

            // Option to fix untitled posts
            if (isset($_POST['fix_titles'])) {
                echo "<h3>Fixing Untitled Posts...</h3>";

                $stmt = $pdo->prepare("
                    UPDATE posts
                    SET title = CONCAT('Post #', post_id)
                    WHERE title IS NULL OR title = ''
                ");
                $stmt->execute();
                $affected = $stmt->rowCount();

                echo "<p class='success'>✓ Fixed $affected posts with default titles</p>";
            } else {
                echo "<form method='POST'>";
                echo "<button type='submit' name='fix_titles'>Fix Untitled Posts</button>";
                echo "</form>";
            }
        } else {
            echo "<p class='success'>✓ All posts have titles</p>";
        }

        // Check for posts without required fields
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE content IS NULL OR content = ''");
        $stmt->execute();
        $emptyContent = $stmt->fetch()['count'];

        if ($emptyContent > 0) {
            echo "<p class='warning'>⚠ $emptyContent posts found with empty content</p>";
        }

        // Show recent posts
        echo "<h3>Recent Posts:</h3>";
        $stmt = $pdo->prepare("SELECT post_id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Created</th></tr>";
        foreach ($posts as $post) {
            echo "<tr>";
            echo "<td>{$post['post_id']}</td>";
            echo "<td>" . htmlspecialchars(substr($post['title'], 0, 50)) . "</td>";
            echo "<td>{$post['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Check users table
    echo "<h2>Users Table Check</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $userTotal = $stmt->fetch()['total'];
    echo "<p>Total users: <strong>$userTotal</strong></p>";

    if ($userTotal > 0) {
        $stmt = $pdo->prepare("SELECT user_id, facility, role FROM users ORDER BY user_id LIMIT 5");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Facility</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>" . htmlspecialchars($user['facility']) . "</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Data Migration Options</h2>";
echo "<p>If you're migrating from an older version, here are common scenarios:</p>";

echo "<h3>1. Posts without Titles</h3>";
echo "<p>Fixed automatically above if you clicked the button.</p>";

echo "<h3>2. Missing Required Columns</h3>";
echo "<p>The migration system handles adding new columns safely.</p>";

echo "<h3>3. Data Export/Import</h3>";
echo "<p>If you need to migrate data from another database:</p>";
echo "<pre>
// Export from old database
mysqldump -u old_user -p old_database posts users > backup.sql

// Import to new database
mysql -u new_user -p new_database < backup.sql
</pre>";

echo "<hr>";
echo "<p><small>Data Migration Helper - " . date('Y-m-d H:i:s') . "</small></p>";
?>