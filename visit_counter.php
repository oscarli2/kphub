<?php
require_once 'db.php';

function incrementPageVisit($page = 'index') {
    global $pdo;
    
    try {
        // Increment visit count
        $stmt = $pdo->prepare("INSERT INTO page_visits (page, visit_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE visit_count = visit_count + 1");
        $stmt->execute([$page]);
        
        // Get current count
        $stmt = $pdo->prepare("SELECT visit_count FROM page_visits WHERE page = ?");
        $stmt->execute([$page]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['visit_count'] : 1;
    } catch (PDOException $e) {
        error_log("Visit counter error: " . $e->getMessage());
        return 0;
    }
}

function getPageVisits($page = 'index') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT visit_count FROM page_visits WHERE page = ?");
        $stmt->execute([$page]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['visit_count'] : 0;
    } catch (PDOException $e) {
        error_log("Visit counter error: " . $e->getMessage());
        return 0;
    }
}
?>