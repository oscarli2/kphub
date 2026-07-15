<?php
require_once 'db.php';
require_once 'page_security.php';

// Initialize page security
PageSecurity::initPageSecurity();

try {
    // Get count of active users (all users with specified roles since we don't track last_login)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM users
        WHERE role IN ('Admin', 'Head', 'Member')
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);

} catch (Exception $e) {
    error_log("Active users count error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'count' => 0,
        'error' => 'Failed to fetch user count'
    ]);
}
?>