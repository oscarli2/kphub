<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$fileUrl = trim($_POST['file_url'] ?? '');
$fileName = trim($_POST['file_name'] ?? '');
$title = trim($_POST['title'] ?? 'Untitled Post'); // Default title if not provided
$content = trim($_POST['content'] ?? '');
$postType = trim($_POST['post_type'] ?? 'file'); // Default to 'file' for backward compatibility
$links = $_POST['links'] ?? null;

// Validate required fields - file_url and file_name are only required for file shares
if (empty($links) && (empty($fileUrl) || empty($fileName))) {
    echo json_encode(['success' => false, 'message' => 'File URL and name are required for file shares']);
    exit;
}

try {
    // First, check if links column exists and add it if not
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'links'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN links TEXT NULL AFTER file_type");
    }

    // Get file extension from URL or filename
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    if (empty($fileExtension)) {
        // Try to get from URL
        $fileExtension = pathinfo(parse_url($fileUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
    }

    // Process links if provided
    $linksJson = null;
    if ($links) {
        $linksArray = json_decode($links, true);
        if ($linksArray && is_array($linksArray)) {
            $processedLinks = [];
            foreach ($linksArray as $link) {
                if (isset($link['url']) && isset($link['label']) && !empty(trim($link['url'])) && !empty(trim($link['label']))) {
                    $processedLinks[] = [
                        'url' => trim($link['url']),
                        'label' => trim($link['label'])
                    ];
                }
            }
            if (!empty($processedLinks)) {
                $linksJson = json_encode($processedLinks);
            }
        }
    }

    // Insert post with Google Drive file/folder
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, post_type, title, content, file_name, file_url, file_type, links) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $postType, $title, $content, $fileName, $fileUrl, $fileExtension, $linksJson]);
    
    $postId = $pdo->lastInsertId();
    
    // Create notifications for all users except the poster
    $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id != ?");
    $userStmt->execute([$userId]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get poster's facility
    $posterStmt = $pdo->prepare("SELECT facility FROM users WHERE user_id = ?");
    $posterStmt->execute([$userId]);
    $posterFacility = $posterStmt->fetch()['facility'];
    
    foreach ($users as $user) {
        $message = $posterFacility . " shared content from Google Drive";
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, post_id, from_user_id) VALUES (?, 'new_post', ?, ?, ?)");
        $notifStmt->execute([$user['user_id'], $message, $postId, $userId]);
    }
    
    echo json_encode(['success' => true, 'message' => ucfirst($postType) . ' shared to newsfeed successfully']);
} catch (PDOException $e) {
    error_log("Share to newsfeed error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to share ' . $postType . ' to newsfeed']);
}
?>