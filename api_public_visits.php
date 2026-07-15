<?php
header('Content-Type: application/json');
require_once 'security.php';

// Security checks
SecurityManager::checkRateLimit();
SecurityManager::validateUserAgent();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'public_visit_logger.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if public visits table exists
if (!checkPublicVisitsTable()) {
    http_response_code(500);
    echo json_encode(['error' => 'Public visits table not found']);
    exit;
}

$action = $_GET['action'] ?? 'stats';
$page = $_GET['page'] ?? null;
$days = (int)($_GET['days'] ?? 7);
$limit = (int)($_GET['limit'] ?? 50);
$offset = (int)($_GET['offset'] ?? 0);

try {
    switch ($action) {
        case 'stats':
            $stats = getPublicVisitStats($page, $days);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'logs':
            $logs = getPublicVisitLogs($page, $limit, $offset);
            echo json_encode([
                'success' => true,
                'data' => $logs
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>