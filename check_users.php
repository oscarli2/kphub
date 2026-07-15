<?php
require_once 'db.php';

try {
    // Get table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Users table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }

    echo "\nFirst 5 users:\n";
    // Get all users
    $stmt = $pdo->query("SELECT * FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        echo "ID: {$user['user_id']}, Name: " . ($user['name'] ?? 'N/A') . ", Facility: " . ($user['facility'] ?? 'N/A') . "\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>