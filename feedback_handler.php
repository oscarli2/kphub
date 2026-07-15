<?php
require_once 'db.php';

function submitFeedback($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO feedback (
                user_id, name, email, rating, category, subject, 
                message, page, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['user_id'] ?? null,
            $data['name'] ?? null,
            $data['email'] ?? null,
            $data['rating'],
            $data['category'],
            $data['subject'],
            $data['message'],
            $data['page'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Feedback submission error: " . $e->getMessage());
        return false;
    }
}

function getFeedbackStats($days = 30) {
    global $pdo;
    
    try {
        // Get overall stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_feedback,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_feedback,
                COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_feedback
            FROM feedback 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get category breakdown
        $stmt = $pdo->prepare("
            SELECT 
                category,
                COUNT(*) as count,
                AVG(rating) as avg_rating
            FROM feedback 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY category
            ORDER BY count DESC
        ");
        $stmt->execute([$days]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent feedback
        $stmt = $pdo->prepare("
            SELECT 
                f.*,
                u.email as user_email
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.user_id
            WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY f.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$days]);
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'stats' => $stats,
            'categories' => $categories,
            'recent' => $recent
        ];
    } catch (PDOException $e) {
        error_log("Feedback stats error: " . $e->getMessage());
        return false;
    }
}

function getFeedbackList($filters = []) {
    global $pdo;
    
    $conditions = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $conditions[] = "f.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['category'])) {
        $conditions[] = "f.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['rating'])) {
        $conditions[] = "f.rating = ?";
        $params[] = $filters['rating'];
    }
    
    if (!empty($filters['days'])) {
        $conditions[] = "f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $filters['days'];
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    $limit = (int)($filters['limit'] ?? 50);
    $offset = (int)($filters['offset'] ?? 0);
    
    try {
        $query = "
            SELECT 
                f.*,
                u.email as user_email,
                u.facility as user_facility
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.user_id
            $whereClause
            ORDER BY f.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Feedback list error: " . $e->getMessage());
        return false;
    }
}

function updateFeedbackStatus($feedbackId, $status, $adminResponse = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE feedback 
            SET status = ?, admin_response = ?, updated_at = NOW()
            WHERE feedback_id = ?
        ");
        
        return $stmt->execute([$status, $adminResponse, $feedbackId]);
    } catch (PDOException $e) {
        error_log("Feedback update error: " . $e->getMessage());
        return false;
    }
}

function checkFeedbackTable() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'feedback'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>