<?php
require_once 'db.php';

try {
    // Get the latest posts
    $stmt = $pdo->query("SELECT post_id, title, content, created_at FROM posts ORDER BY post_id DESC LIMIT 5");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Latest posts:\n";
    foreach ($posts as $post) {
        echo "ID: {$post['post_id']}, Title: {$post['title']}, Created: {$post['created_at']}\n";
    }

    // Check post_attachments table
    $stmt = $pdo->query("SELECT post_id, file_name FROM post_attachments ORDER BY post_id DESC LIMIT 5");
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nLatest attachments:\n";
    foreach ($attachments as $attachment) {
        echo "Post ID: {$attachment['post_id']}, File: {$attachment['file_name']}\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>