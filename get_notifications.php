<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get unread count
    $unreadStmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $unreadStmt->execute([$userId]);
    $unreadCount = $unreadStmt->fetch()['count'];
    
    // Get recent notifications
    $notificationsStmt = $pdo->prepare("
        SELECT notification_id, type, message, is_read, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $notificationsStmt->execute([$userId]);
    $notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'unread_count' => (int)$unreadCount,
        'notifications' => $notifications
    ]);
    
} catch (PDOException $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
}
?>