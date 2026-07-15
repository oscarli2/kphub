<?php
/**
 * Add Missing Columns to Production Database
 *
 * Safely adds missing columns to your existing posts table
 * without affecting any existing data
 */

echo "<h1>Add Missing Columns</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .warning { color: orange; }</style>";

try {
    require 'db.php';
    echo "<p class='success'>✓ Connected to production database: $dbname</p>";

    // Check current posts table structure
    echo "<h2>Current Posts Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $existingColumns = array_column($columns, 'Field');
    echo "<p>Found " . count($existingColumns) . " columns: " . implode(', ', $existingColumns) . "</p>";

    // Columns that might be missing
    $columnsToAdd = [
        'title' => "ALTER TABLE posts ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Untitled Post' AFTER user_id",
        'links' => "ALTER TABLE posts ADD COLUMN links TEXT NULL AFTER file_type"
    ];

    echo "<h2>Adding Missing Columns</h2>";

    $addedCount = 0;
    foreach ($columnsToAdd as $columnName => $sql) {
        if (!in_array($columnName, $existingColumns)) {
            echo "<p>Adding column: <strong>$columnName</strong>...</p>";

            try {
                $pdo->exec($sql);
                echo "<p class='success'>✓ Successfully added column '$columnName'</p>";
                $addedCount++;
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='success'>✓ Column '$columnName' already exists</p>";
        }
    }

    if ($addedCount > 0) {
        echo "<h2>✅ Success!</h2>";
        echo "<p>Added $addedCount column(s) to your posts table.</p>";
        echo "<p>Your existing data is preserved and the posts should now display correctly.</p>";
    } else {
        echo "<h2>ℹ️ No Changes Needed</h2>";
        echo "<p>All required columns already exist in your posts table.</p>";
    }

    // Verify the fix
    echo "<h2>Verification</h2>";
    echo "<p>Testing the posts query that was failing...</p>";

    try {
        $stmt = $pdo->prepare("
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
            LIMIT 5
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<p class='success'>✓ Query now works! Retrieved " . count($posts) . " posts.</p>";

        if (!empty($posts)) {
            echo "<h3>Sample Posts:</h3>";
            echo "<ul>";
            foreach ($posts as $post) {
                echo "<li><strong>{$post['title']}</strong> by {$post['author_facility']} ({$post['created_at']})</li>";
            }
            echo "</ul>";
        }

    } catch (PDOException $e) {
        echo "<p class='error'>❌ Query still failing: " . $e->getMessage() . "</p>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Test your KP-HUB site</strong> - posts should now display</li>";
echo "<li><strong>Delete this file</strong> from your server for security</li>";
echo "<li>If posts still don't show, check browser developer tools for other errors</li>";
echo "</ol>";

echo "<hr>";
echo "<p><small>Column addition completed: " . date('Y-m-d H:i:s') . "</small></p>";
?>