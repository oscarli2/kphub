<?php
/**
 * Database Schema Comparison
 *
 * Compares your production database schema with what the code expects
 */

echo "<h1>Database Schema Comparison</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .warning { color: orange; } table { border-collapse: collapse; width: 100%; margin: 10px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .missing { background-color: #ffe6e6; } .extra { background-color: #e6ffe6; }</style>";

try {
    require 'db.php';
    echo "<p class='success'>✓ Connected to production database: $dbname</p>";

    // Expected schema for posts table
    $expectedPostsColumns = [
        'post_id' => 'int(11)',
        'user_id' => 'int(11)',
        'title' => 'varchar(255)',
        'post_type' => 'enum(\'text\',\'file\',\'folder\')',
        'content' => 'text',
        'file_name' => 'varchar(255)',
        'file_url' => 'text',
        'file_type' => 'varchar(10)',
        'links' => 'text',
        'file_id' => 'varchar(100)',
        'folder_id' => 'varchar(100)',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    // Check posts table
    echo "<h2>Posts Table Schema Check</h2>";

    try {
        $stmt = $pdo->query("DESCRIBE posts");
        $actualColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table>";
        echo "<tr><th>Column</th><th>Expected Type</th><th>Actual Type</th><th>Status</th></tr>";

        foreach ($expectedPostsColumns as $colName => $expectedType) {
            $found = false;
            $actualType = 'MISSING';

            foreach ($actualColumns as $actualCol) {
                if ($actualCol['Field'] === $colName) {
                    $found = true;
                    $actualType = $actualCol['Type'];
                    break;
                }
            }

            $status = $found ? ($actualType === $expectedType ? "<span class='success'>✓ Match</span>" : "<span class='warning'>⚠ Type mismatch</span>") : "<span class='error'>✗ Missing</span>";

            echo "<tr>";
            echo "<td>$colName</td>";
            echo "<td>$expectedType</td>";
            echo "<td>$actualType</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }

        // Check for extra columns
        foreach ($actualColumns as $actualCol) {
            if (!isset($expectedPostsColumns[$actualCol['Field']])) {
                echo "<tr class='extra'>";
                echo "<td>{$actualCol['Field']}</td>";
                echo "<td>(not expected)</td>";
                echo "<td>{$actualCol['Type']}</td>";
                echo "<td><span class='warning'>⚠ Extra column</span></td>";
                echo "</tr>";
            }
        }

        echo "</table>";

    } catch (PDOException $e) {
        echo "<p class='error'>❌ Posts table not found or error: " . $e->getMessage() . "</p>";
    }

    // Check users table
    echo "<h2>Users Table Schema Check</h2>";

    $expectedUsersColumns = [
        'user_id' => 'int(11)',
        'email' => 'varchar(100)',
        'password' => 'varchar(255)',
        'facility' => 'varchar(50)',
        'role' => 'enum(\'Admin\',\'Head\',\'User\')',
        'can_create_folder' => 'tinyint(1)',
        'can_delete' => 'tinyint(1)',
        'can_generate_report' => 'tinyint(1)',
        'profile_picture' => 'varchar(255)'
    ];

    try {
        $stmt = $pdo->query("DESCRIBE users");
        $actualColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table>";
        echo "<tr><th>Column</th><th>Expected Type</th><th>Actual Type</th><th>Status</th></tr>";

        foreach ($expectedUsersColumns as $colName => $expectedType) {
            $found = false;
            $actualType = 'MISSING';

            foreach ($actualColumns as $actualCol) {
                if ($actualCol['Field'] === $colName) {
                    $found = true;
                    $actualType = $actualCol['Type'];
                    break;
                }
            }

            $status = $found ? ($actualType === $expectedType ? "<span class='success'>✓ Match</span>" : "<span class='warning'>⚠ Type mismatch</span>") : "<span class='error'>✗ Missing</span>";

            echo "<tr>";
            echo "<td>$colName</td>";
            echo "<td>$expectedType</td>";
            echo "<td>$actualType</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }

        echo "</table>";

    } catch (PDOException $e) {
        echo "<p class='error'>❌ Users table not found or error: " . $e->getMessage() . "</p>";
    }

    // Check other required tables
    echo "<h2>Other Required Tables</h2>";
    $requiredTables = ['post_reactions', 'file_downloads', 'newsfeed_sections', 'newsfeed_section_links'];

    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;

            if ($exists) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
                $stmt->execute();
                $count = $stmt->fetch()['count'];
                echo "<p class='success'>✓ $table table exists ($count records)</p>";
            } else {
                echo "<p class='warning'>⚠ $table table missing</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error checking $table: " . $e->getMessage() . "</p>";
        }
    }

    // Check migrations table
    echo "<h2>Migration Status</h2>";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
        $exists = $stmt->rowCount() > 0;

        if ($exists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM schema_migrations WHERE status = 'success'");
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            echo "<p class='success'>✓ Migrations table exists ($count successful migrations)</p>";
        } else {
            echo "<p class='warning'>⚠ Migrations table missing - run deployment first</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error checking migrations: " . $e->getMessage() . "</p>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Common Issues & Fixes</h2>";
echo "<ul>";
echo "<li><strong>Missing columns:</strong> Run the deployment script to add missing columns</li>";
echo "<li><strong>Type mismatches:</strong> May cause data display issues - check with your original schema</li>";
echo "<li><strong>Missing tables:</strong> Run deployment to create required tables</li>";
echo "<li><strong>No migrations applied:</strong> Run <code>php deploy.php</code> on your server</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Schema analysis generated: " . date('Y-m-d H:i:s') . "</small></p>";
?>