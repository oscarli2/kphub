<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['post_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID required']);
    exit;
}

$postId = (int)$_GET['post_id'];
$userId = $_SESSION['user_id'] ?? null;

try {
    // Get the file info
    $fileStmt = $pdo->prepare("SELECT file_url, file_name FROM posts WHERE post_id = ? AND post_type = 'file'");
    $fileStmt->execute([$postId]);
    $file = $fileStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    // Track the download
    if ($userId) {
        // Check if user already downloaded this file
        $checkStmt = $pdo->prepare("SELECT download_count FROM file_downloads WHERE post_id = ? AND user_id = ?");
        $checkStmt->execute([$postId, $userId]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Increment existing download count
            $updateStmt = $pdo->prepare("UPDATE file_downloads SET download_count = download_count + 1, last_downloaded = CURRENT_TIMESTAMP WHERE post_id = ? AND user_id = ?");
            $updateStmt->execute([$postId, $userId]);
        } else {
            // Create new download record
            $insertStmt = $pdo->prepare("INSERT INTO file_downloads (post_id, user_id, download_count) VALUES (?, ?, 1)");
            $insertStmt->execute([$postId, $userId]);
        }
    } else {
        // For non-logged in users, create anonymous download record
        $insertStmt = $pdo->prepare("INSERT INTO file_downloads (post_id, user_id, download_count) VALUES (?, NULL, 1)");
        $insertStmt->execute([$postId]);
    }
    
    // Get total download count
    $totalStmt = $pdo->prepare("SELECT SUM(download_count) as total FROM file_downloads WHERE post_id = ?");
    $totalStmt->execute([$postId]);
    $total = $totalStmt->fetch()['total'] ?? 0;
    
    // Return file info for download
    echo json_encode([
        'success' => true,
        'file_url' => $file['file_url'],
        'file_name' => $file['file_name'],
        'total_downloads' => (int)$total
    ]);
    
} catch (PDOException $e) {
    error_log("Download tracking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>