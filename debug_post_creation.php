<?php
require 'db.php';

echo "Testing database connection...\n";
try {
    $stmt = $pdo->query('SELECT 1');
    echo "✅ Database connection OK\n";
} catch(Exception $e) {
    echo "❌ Database connection ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nTesting post_attachments table...\n";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM post_attachments');
    $result = $stmt->fetch();
    echo "✅ post_attachments table exists with {$result['count']} records\n";
} catch(Exception $e) {
    echo "❌ post_attachments table ERROR: " . $e->getMessage() . "\n";
}

echo "\nTesting posts table...\n";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM posts');
    $result = $stmt->fetch();
    echo "✅ posts table exists with {$result['count']} records\n";
} catch(Exception $e) {
    echo "❌ posts table ERROR: " . $e->getMessage() . "\n";
}

echo "\n✅ All database checks passed!\n";
?>