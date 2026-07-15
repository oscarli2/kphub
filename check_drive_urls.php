<?php
require 'db.php';
try {
    $stmt = $pdo->prepare('SELECT post_id, title, file_name, file_url, post_type FROM posts WHERE file_url LIKE "%drive.google.com%" ORDER BY created_at DESC LIMIT 5');
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Google Drive posts in database:' . PHP_EOL;
    foreach ($posts as $post) {
        echo 'ID: ' . $post['post_id'] . ' - ' . $post['title'] . ' - ' . $post['file_name'] . ' - ' . $post['file_url'] . ' - Type: ' . $post['post_type'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>