<?php
/**
 * Quick Posts Test
 * Simple script to test if posts API is working
 */

header('Content-Type: application/json');

try {
    require 'db.php';

    // Test database connection
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $count = $stmt->fetch()['count'];

    // Get a few posts
    $stmt = $pdo->prepare("SELECT post_id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_posts' => $count,
        'sample_posts' => $posts,
        'database' => $dbname,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'database' => $dbname ?? 'unknown'
    ]);
}
?>