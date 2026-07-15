<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if available
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

require 'db.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(null);
  exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email, facility, role, can_create_folder, can_delete, can_delete_files, can_generate_report, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
  echo json_encode([
    'user_id' => $userId,
    'username' => $user['email'],
    'email' => $user['email'],
    'facility' => $user['facility'],
    'role' => $user['role'],
    'can_create_folder' => (bool)$user['can_create_folder'],
    'can_delete' => (bool)$user['can_delete'],
    'can_delete_files' => (bool)$user['can_delete_files'],
    'profile_picture' => $user['profile_picture'] ? '/uploads/thumbnails/' . $user['profile_picture'] : '/uploads/default.png',
    'can_generate_report' => (bool)$user['can_generate_report'],
    'folder_id' => $_SESSION['folder_id'] // ✅ must be here!
  ]);
} else {
  echo json_encode(null);
}
