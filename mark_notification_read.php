<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['notification_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>