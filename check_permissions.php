<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo 'No user session found' . PHP_EOL;
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email, facility, role, can_create_folder, can_delete, can_delete_files, can_generate_report, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo 'User: ' . $user['email'] . PHP_EOL;
    echo 'Role: ' . $user['role'] . PHP_EOL;
    echo 'can_delete_files: ' . ($user['can_delete_files'] ? 'true' : 'false') . PHP_EOL;
    echo 'can_create_folder: ' . ($user['can_create_folder'] ? 'true' : 'false') . PHP_EOL;
    echo 'can_delete: ' . ($user['can_delete'] ? 'true' : 'false') . PHP_EOL;
    echo 'can_generate_report: ' . ($user['can_generate_report'] ? 'true' : 'false') . PHP_EOL;
} else {
    echo 'User not found in database' . PHP_EOL;
}
?>