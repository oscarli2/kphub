<?php
require_once 'db.php';

function logPublicVisit($page) {
    global $pdo;
    
    // Get visitor information
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Skip logging for common bots/crawlers
    if ($user_agent && preg_match('/bot|crawler|spider|scraper/i', $user_agent)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public_visits (page, ip_address, user_agent) 
            VALUES (?, ?, ?)
        ");
        
        return $stmt->execute([$page, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        error_log("Public visit logging error: " . $e->getMessage());
        return false;
    }
}

function getPublicVisitStats($page = null, $days = 7) {
    global $pdo;
    
    // Build WHERE clause properly
    $conditions = ["visit_time >= DATE_SUB(NOW(), INTERVAL ? DAY)"];
    $params = [$days];
    
    if ($page) {
        $conditions[] = "page = ?";
        $params[] = $page;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $conditions);
    
    // Get total visits
    $totalQuery = "
        SELECT COUNT(*) as total_visits 
        FROM public_visits 
        $whereClause
    ";
    
    $stmt = $pdo->prepare($totalQuery);
    $stmt->execute($params);
    $totalVisits = $stmt->fetch(PDO::FETCH_ASSOC)['total_visits'];
    
    // Get unique IP visits (approximate unique visitors)
    $uniqueQuery = "
        SELECT COUNT(DISTINCT ip_address) as unique_visitors 
        FROM public_visits 
        $whereClause
    ";
    $stmt = $pdo->prepare($uniqueQuery);
    $stmt->execute($params);
    $uniqueVisitors = $stmt->fetch(PDO::FETCH_ASSOC)['unique_visitors'];
    
    // Get daily breakdown
    $dailyQuery = "
        SELECT 
            DATE(visit_time) as visit_date,
            COUNT(*) as visits,
            COUNT(DISTINCT ip_address) as unique_visitors
        FROM public_visits 
        $whereClause
        GROUP BY DATE(visit_time)
        ORDER BY visit_date DESC
    ";
    $stmt = $pdo->prepare($dailyQuery);
    $stmt->execute($params);
    $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_visits' => $totalVisits,
        'unique_visitors' => $uniqueVisitors,
        'daily_breakdown' => $dailyStats
    ];
}

function getPublicVisitLogs($page = null, $limit = 50, $offset = 0) {
    global $pdo;
    
    $whereClause = $page ? "WHERE page = ?" : "";
    $params = $page ? [$page] : [];
    
    $query = "
        SELECT 
            visit_id,
            page,
            ip_address,
            LEFT(user_agent, 100) as user_agent,
            visit_time
        FROM public_visits 
        $whereClause
        ORDER BY visit_time DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if table exists (for safety)
function checkPublicVisitsTable() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'public_visits'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>