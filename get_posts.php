<?php
require 'db.php';

header('Content-Type: application/json');

try {
    // Get current user ID if logged in (optional for public newsfeed)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    // Check if requesting a single post
    $singlePostId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : null;
    
    if ($singlePostId) {
        // Get single post
        $stmt = $pdo->prepare("
            SELECT 
                p.post_id,
                p.user_id,
                p.title,
                p.post_type,
                p.content,
                p.file_name,
                p.file_url,
                p.file_type,
                p.links,
                p.created_at,
                p.updated_at,
                CASE 
                    WHEN u.facility IN ('MMKI', 'CAPDEV', 'PUBLIC ED', 'LINKAGE', 'INSTI-LEGAL') THEN CONCAT(u.facility, ' Facility')
                    ELSE CONCAT(u.facility, ' (Sub-LGRC)')
                END as author_facility
            FROM posts p 
            JOIN users u ON p.user_id = u.user_id 
            WHERE p.post_id = ?
        ");
        $stmt->execute([$singlePostId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            echo json_encode(['error' => 'Post not found']);
            exit;
        }
        
        echo json_encode(['posts' => [$post]]);
        exit;
    }
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(5, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get search parameter
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Build WHERE clause for search
    $whereClause = '';
    $searchParams = [];
    if (!empty($search)) {
        $whereClause = "WHERE (p.title LIKE ? OR p.content LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $searchParams = [$searchTerm, $searchTerm];
    }
    
    // Get total post count for pagination info
    $countQuery = "SELECT COUNT(*) as total FROM posts p $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalPosts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalPosts / $limit);
    
    // Get posts with user info
    $postsQuery = "
        SELECT 
            p.post_id,
            p.user_id,
            p.title,
            p.post_type,
            p.content,
            p.file_name,
            p.file_url,
            p.file_type,
            p.links,
            p.created_at,
            CASE 
                WHEN u.facility IN ('MMKI', 'CAPDEV', 'PUBLIC ED', 'LINKAGE', 'INSTI-LEGAL') THEN CONCAT(u.facility, ' Facility')
                ELSE CONCAT(u.facility, ' (Sub-LGRC)')
            END as author_facility
        FROM posts p 
        JOIN users u ON p.user_id = u.user_id 
        $whereClause
        ORDER BY p.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($postsQuery);
    $stmt->execute($searchParams);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attachments for each post
    $postIds = array_column($posts, 'post_id');
    $attachments = [];
    if (!empty($postIds)) {
        $placeholders = str_repeat('?,', count($postIds) - 1) . '?';
        $attachmentStmt = $pdo->prepare("
            SELECT post_id, file_name, file_url, file_type, file_size
            FROM post_attachments 
            WHERE post_id IN ($placeholders)
            ORDER BY attachment_id ASC
        ");
        $attachmentStmt->execute($postIds);
        $attachmentResults = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($attachmentResults as $attachment) {
            $attachments[$attachment['post_id']][] = $attachment;
        }
    }
    
    // Add attachments to posts
    foreach ($posts as &$post) {
        $post['attachments'] = $attachments[$post['post_id']] ?? [];
    }
    
    // Get reactions for each post
    foreach ($posts as &$post) {
        $postId = $post['post_id'];
        
        // Get download count for file posts
        if ($post['post_type'] === 'file') {
            $downloadStmt = $pdo->prepare("SELECT SUM(download_count) as total FROM file_downloads WHERE post_id = ?");
            $downloadStmt->execute([$postId]);
            $post['download_count'] = (int)($downloadStmt->fetch()['total'] ?? 0);
        }
        
        // Get reaction counts and user's reactions
        $reactionStmt = $pdo->prepare("
            SELECT 
                reaction_type,
                COUNT(*) as count,
                SUM(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as user_reacted
            FROM post_reactions 
            WHERE post_id = ? 
            GROUP BY reaction_type
        ");
        $reactionStmt->execute([$currentUserId, $postId]);
        $reactions = $reactionStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $post['reactions'] = [];
        foreach ($reactions as $reaction) {
            $post['reactions'][$reaction['reaction_type']] = [
                'count' => (int)$reaction['count'],
                'user_reacted' => (bool)$reaction['user_reacted']
            ];
        }
    }
    
    echo json_encode([
        'posts' => $posts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_posts' => (int)$totalPosts,
            'posts_per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);
} catch (PDOException $e) {
    error_log("Get posts error: " . $e->getMessage());
    echo json_encode([
        'posts' => [],
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 0,
            'total_posts' => 0,
            'posts_per_page' => 10,
            'has_next' => false,
            'has_prev' => false
        ]
    ]);
}
?>