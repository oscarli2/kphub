<?php
// Turn off error reporting to prevent HTML errors from interfering with JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'security.php';

// Security checks  
SecurityManager::checkRateLimit();
SecurityManager::validateUserAgent();

require 'session_validate.php';
require 'upload_logger.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Set proper JSON header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'logs':
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;
            $user_filter = $_GET['user_filter'] ?? null;
            $date_filter = $_GET['date_filter'] ?? null;
            
            $logs = getUploadLogs($limit, $offset, $user_filter, $date_filter);
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM upload_logs ul LEFT JOIN users u ON ul.user_id = u.user_id WHERE 1=1";
            $countParams = [];
            
            if ($user_filter) {
                $countQuery .= " AND u.email LIKE ?";
                $countParams[] = "%$user_filter%";
            }
            
            if ($date_filter) {
                switch ($date_filter) {
                    case 'today':
                        $countQuery .= " AND DATE(ul.upload_time) = CURDATE()";
                        break;
                    case 'week':
                        $countQuery .= " AND ul.upload_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $countQuery .= " AND ul.upload_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        break;
                }
            }
            
            $stmt = $pdo->prepare($countQuery);
            $stmt->execute($countParams);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'logs' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'stats':
            $stats = getUploadStats();
            
            // Ensure we always have valid data structure
            $defaultStats = [
                'today' => 0,
                'week' => 0,
                'month' => 0,
                'top_uploaders' => [],
                'file_types' => []
            ];
            
            $stats = array_merge($defaultStats, $stats);
            
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;

        case 'uploads_by_facility':
            // Return upload counts grouped by facility for a given quarter and year
            $rawQuarter = $_GET['quarter'] ?? '';
            $quarter = strtoupper(trim($rawQuarter));
            $year = (int)($_GET['year'] ?? date('Y'));

            // Map quarter to month range
            $quarterMap = [
                'Q1' => [1,3],
                'Q2' => [4,6],
                'Q3' => [7,9],
                'Q4' => [10,12]
            ];

            // If quarter param is missing, try to infer from optional month param or current month
            if ($quarter === '') {
                $month = (int)($_GET['month'] ?? date('n'));
                if ($month >= 1 && $month <= 3) $quarter = 'Q1';
                elseif ($month >= 4 && $month <= 6) $quarter = 'Q2';
                elseif ($month >= 7 && $month <= 9) $quarter = 'Q3';
                else $quarter = 'Q4';
            }

            if (!isset($quarterMap[$quarter])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid quarter', 'received_quarter' => $rawQuarter], JSON_UNESCAPED_UNICODE);
                break;
            }

            list($mStart, $mEnd) = $quarterMap[$quarter];

            $sql = "SELECT COALESCE(u.facility, 'Unknown') AS facility, COUNT(*) AS uploads
                    FROM upload_logs ul
                    LEFT JOIN users u ON ul.user_id = u.user_id
                    WHERE MONTH(ul.upload_time) BETWEEN :mStart AND :mEnd
                      AND YEAR(ul.upload_time) = :year
                    GROUP BY facility
                    ORDER BY uploads DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':mStart' => $mStart, ':mEnd' => $mEnd, ':year' => $year]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'test':
            // Debug endpoint to check database connection
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'upload_logs'");
                $tableExists = $stmt->rowCount() > 0;
                
                echo json_encode([
                    'status' => 'success',
                    'table_exists' => $tableExists,
                    'pdo_connected' => isset($pdo),
                    'session_user_id' => $_SESSION['user_id'] ?? null,
                    'session_role' => $_SESSION['role'] ?? null
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'error' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>