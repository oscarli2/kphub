<?php
require 'db.php';

// Test get_posts.php for a folder post
$url = 'http://localhost/kphub/get_posts.php?post_id=68';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);
$data = json_decode($response, true);

echo 'Response from get_posts.php for folder post ID 68:' . PHP_EOL;
echo json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
?>