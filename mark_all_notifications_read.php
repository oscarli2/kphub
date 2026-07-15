<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Mark all notifications read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>