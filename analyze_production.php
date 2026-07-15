<?php
/**
 * Production Database Analysis Script
 *
 * Checks what's actually in your live production database
 * without modifying any data
 */

echo "<h1>Production Database Analysis</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .warning { color: orange; } table { border-collapse: collapse; width: 100%; margin: 10px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

try {
    require 'db.php';
    echo "<p class='success'>✓ Connected to production database: $dbname</p>";

    // Check all tables
    echo "<h2>Database Tables</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo "<li><strong>$table</strong>: $count records</li>";
    }
    echo "</ul>";

    // Detailed posts analysis
    if (in_array('posts', $tables)) {
        echo "<h2>Posts Table Analysis</h2>";

        // Posts table structure
        $stmt = $pdo->query("DESCRIBE posts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Table Structure:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Sample posts data
        $stmt = $pdo->prepare("SELECT post_id, user_id, title, post_type, content, created_at FROM posts ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Recent Posts (Last 10):</h3>";
        if (empty($posts)) {
            echo "<p class='warning'>⚠ No posts found in the database!</p>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>User</th><th>Title</th><th>Type</th><th>Content Preview</th><th>Created</th></tr>";
            foreach ($posts as $post) {
                $contentPreview = substr(strip_tags($post['content']), 0, 50) . (strlen($post['content']) > 50 ? '...' : '');
                echo "<tr>";
                echo "<td>{$post['post_id']}</td>";
                echo "<td>{$post['user_id']}</td>";
                echo "<td>" . htmlspecialchars(substr($post['title'], 0, 30)) . "</td>";
                echo "<td>{$post['post_type']}</td>";
                echo "<td>" . htmlspecialchars($contentPreview) . "</td>";
                echo "<td>{$post['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        // Check for posts with issues
        echo "<h3>Posts with Potential Issues:</h3>";

        // Posts without titles
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE title IS NULL OR title = ''");
        $stmt->execute();
        $noTitle = $stmt->fetch()['count'];
        echo "<p>Posts without titles: <strong>$noTitle</strong></p>";

        // Posts without content
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE content IS NULL OR content = ''");
        $stmt->execute();
        $noContent = $stmt->fetch()['count'];
        echo "<p>Posts without content: <strong>$noContent</strong></p>";

        // Posts by user
        $stmt = $pdo->prepare("SELECT user_id, COUNT(*) as count FROM posts GROUP BY user_id ORDER BY count DESC");
        $stmt->execute();
        $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Posts by User:</h3>";
        echo "<table>";
        echo "<tr><th>User ID</th><th>Post Count</th></tr>";
        foreach ($userStats as $stat) {
            echo "<tr><td>{$stat['user_id']}</td><td>{$stat['count']}</td></tr>";
        }
        echo "</table>";
    }

    // Check users table
    if (in_array('users', $tables)) {
        echo "<h2>Users Analysis</h2>";

        $stmt = $pdo->prepare("SELECT user_id, email, facility, role FROM users ORDER BY user_id");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table>";
        echo "<tr><th>ID</th><th>Email</th><th>Facility</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>{$user['facility']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test the actual API query that the frontend uses
    echo "<h2>API Query Test</h2>";
    echo "<p>Testing the exact query used by get_posts.php...</p>";

    $page = 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $postsQuery = "
        SELECT
            p.post_id,
            p.user_id,
            p.title,
            p.post_type,
            p.content,
            p.file_name,
            p.file_url,
            p.file_type,
            p.links,
            p.created_at,
            u.facility as author_facility
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($postsQuery);
    $stmt->execute();
    $apiPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>API query returned: <strong>" . count($apiPosts) . "</strong> posts</p>";

    if (!empty($apiPosts)) {
        echo "<h3>API Response Preview:</h3>";
        echo "<pre>" . json_encode($apiPosts, JSON_PRETTY_PRINT) . "</pre>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Current database config: $dbname on $host</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If posts exist in database but API returns 0: Check for JOIN issues or user permissions</li>";
echo "<li>If posts exist but have no titles: The title column might be missing or NULL</li>";
echo "<li>If users table is empty: User authentication might be failing</li>";
echo "<li>Check browser Network tab when loading the page to see if API calls are failing</li>";
echo "</ol>";

echo "<hr>";
echo "<p><small>Analysis generated: " . date('Y-m-d H:i:s') . "</small></p>";
?>