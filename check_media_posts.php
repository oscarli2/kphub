<?php
require 'db.php';

$stmt = $pdo->query('SELECT p.post_id, p.title, COUNT(a.attachment_id) as media_count FROM posts p LEFT JOIN post_attachments a ON p.post_id = a.post_id WHERE a.file_type IN ("jpg", "jpeg", "png", "gif", "mp4", "mov") GROUP BY p.post_id HAVING media_count > 1 ORDER BY p.created_at DESC LIMIT 3');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Posts with multiple media attachments:' . PHP_EOL;
foreach ($posts as $post) {
    echo 'Post ID: ' . $post['post_id'] . ' - Title: ' . $post['title'] . ' - Media count: ' . $post['media_count'] . PHP_EOL;
}
?>