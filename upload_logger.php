<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';
require 'db.php';

// Helper function to log file uploads
function logFileUpload($user_id, $filename, $file_size = null, $file_type = null, $upload_location = null, $upload_type = 'post_attachment') {
    global $pdo;
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO upload_logs (user_id, filename, file_size, file_type, upload_location, upload_type, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $filename,
            $file_size,
            $file_type,
            $upload_location,
            $upload_type,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Upload logging error: " . $e->getMessage());
        return false;
    }
}

// Function to get upload logs for admin monitoring
function getUploadLogs($limit = 50, $offset = 0, $user_filter = null, $date_filter = null) {
    global $pdo;
    
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Debug: Log the input parameters
        error_log("getUploadLogs called with: limit=$limit, offset=$offset, user_filter=$user_filter, date_filter=$date_filter");
        
        if ($user_filter) {
            $whereClause .= " AND u.email LIKE ?";
            $params[] = "%$user_filter%";
        }
        
        if ($date_filter) {
            switch ($date_filter) {
                case 'today':
                    $whereClause .= " AND DATE(ul.upload_time) = CURDATE()";
                    break;
                case 'week':
                    $whereClause .= " AND ul.upload_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $whereClause .= " AND ul.upload_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }
        
        $sql = "
            SELECT 
                ul.*,
                u.email,
                u.facility,
                u.role
            FROM upload_logs ul
            LEFT JOIN users u ON ul.user_id = u.user_id
            $whereClause
            ORDER BY ul.upload_time DESC
            LIMIT $limit OFFSET $offset
        ";
        
        // Debug: Log the final SQL
        error_log("getUploadLogs SQL: " . $sql);
        error_log("getUploadLogs params: " . json_encode($params));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the result count
        error_log("getUploadLogs returned " . count($result) . " records");
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Get upload logs error: " . $e->getMessage());
        return [];
    }
}

// Function to get upload statistics
function getUploadStats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total uploads today
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM upload_logs WHERE DATE(upload_time) = CURDATE()");
        $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total uploads this week
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM upload_logs WHERE upload_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total uploads this month
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM upload_logs WHERE upload_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Most active users this week
        $stmt = $pdo->query("
            SELECT 
                u.email, 
                u.facility,
                COUNT(*) as upload_count
            FROM upload_logs ul
            LEFT JOIN users u ON ul.user_id = u.user_id
            WHERE ul.upload_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY ul.user_id
            ORDER BY upload_count DESC
            LIMIT 5
        ");
        $stats['top_uploaders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // File type breakdown this month
        $stmt = $pdo->query("
            SELECT 
                COALESCE(file_type, 'Unknown') as file_type,
                COUNT(*) as count
            FROM upload_logs
            WHERE upload_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY file_type
            ORDER BY count DESC
            LIMIT 10
        ");
        $stats['file_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Get upload stats error: " . $e->getMessage());
        return [];
    }
}
?>