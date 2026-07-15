<?php
require 'db.php';
$stmt = $pdo->query('SELECT user_id, email, role, can_delete_files FROM users ORDER BY user_id');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Users and their delete file permissions:' . PHP_EOL;
foreach ($users as $user) {
    echo $user['user_id'] . ': ' . $user['email'] . ' (' . $user['role'] . ') - can_delete_files: ' . ($user['can_delete_files'] ? 'YES' : 'NO') . PHP_EOL;
}
?>