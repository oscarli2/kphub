<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if (!$postId) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID is required']);
    exit;
}

try {
    // Check if the post exists and belongs to the user
    $stmt = $pdo->prepare("SELECT user_id, file_url FROM posts WHERE post_id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    if ($post['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only delete your own posts']);
        exit;
    }

    // Delete associated file if it exists
    if (!empty($post['file_url'])) {
        $filePath = ltrim($post['file_url'], '/'); // Remove leading slash for local file path
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Delete reactions first (foreign key constraint)
    $deleteReactionsStmt = $pdo->prepare("DELETE FROM post_reactions WHERE post_id = ?");
    $deleteReactionsStmt->execute([$postId]);

    // Delete file downloads records
    $deleteDownloadsStmt = $pdo->prepare("DELETE FROM file_downloads WHERE post_id = ?");
    $deleteDownloadsStmt->execute([$postId]);

    // Delete the post
    $deletePostStmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
    $deletePostStmt->execute([$postId]);

    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete post: ' . $e->getMessage()]);
}
?>