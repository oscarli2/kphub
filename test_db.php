<?php
require 'db.php';
try {
    $stmt = $pdo->query('SHOW TABLES LIKE "posts"');
    if ($stmt->rowCount() > 0) {
        echo 'Posts table exists' . PHP_EOL;
        $stmt = $pdo->query('DESCRIBE posts');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . PHP_EOL;
        }
    } else {
        echo 'Posts table does not exist' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Database error: ' . $e->getMessage() . PHP_EOL;
}
?>