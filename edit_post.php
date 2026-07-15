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
$newTitle = isset($_POST['title']) ? trim($_POST['title']) : '';
$newContent = isset($_POST['content']) ? trim($_POST['content']) : '';
$links = isset($_POST['links']) ? $_POST['links'] : null;

if (!$postId) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID is required']);
    exit;
}

if (empty($newTitle)) {
    http_response_code(400);
    echo json_encode(['error' => 'Post title cannot be empty']);
    exit;
}

if (empty($newContent)) {
    http_response_code(400);
    echo json_encode(['error' => 'Post content cannot be empty']);
    exit;
}

try {
    // First, check if title column exists and add it if not
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'title'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Untitled Post' AFTER user_id");
    }

    // First, check if links column exists and add it if not
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'links'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN links TEXT NULL AFTER file_name");
    }

    // Check if the post exists and belongs to the user
    $stmt = $pdo->prepare("SELECT user_id, post_type, file_url FROM posts WHERE post_id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    if ($post['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only edit your own posts']);
        exit;
    }

    // Handle file upload if provided
    $fileUrl = $post['file_url']; // Keep existing file by default
    $fileName = null;
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        // Delete old file if it exists
        if ($post['file_url'] && file_exists(str_replace('/', '', $post['file_url']))) {
            unlink(str_replace('/', '', $post['file_url']));
        }
        
        // Handle new file upload
        $uploadDir = 'uploads/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $originalName = $_FILES['attachment']['name'];
        $fileName = $originalName;
        $fileUrl = '/' . $uploadDir . time() . '_' . $originalName;
        
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], ltrim($fileUrl, '/'))) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload attachment']);
            exit;
        }
    } else if (isset($_POST['remove_attachment']) && $_POST['remove_attachment'] === 'true') {
        // Remove existing attachment
        if ($post['file_url'] && file_exists(str_replace('/', '', $post['file_url']))) {
            unlink(str_replace('/', '', $post['file_url']));
        }
        $fileUrl = null;
        $fileName = null;
    } else {
        // Keep existing file
        $stmt = $pdo->prepare("SELECT file_name FROM posts WHERE post_id = ?");
        $stmt->execute([$postId]);
        $existing = $stmt->fetch();
        $fileName = $existing['file_name'];
    }

    // Process links if provided
    $linksJson = null;
    if ($links) {
        // Decode JSON string if it's a string
        if (is_string($links)) {
            $links = json_decode($links, true);
        }
        
        if (is_array($links)) {
            $processedLinks = [];
            foreach ($links as $link) {
                if (!empty($link['url']) && !empty($link['label'])) {
                    $processedLinks[] = [
                        'url' => filter_var($link['url'], FILTER_SANITIZE_URL),
                        'label' => htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8')
                    ];
                }
            }
            if (!empty($processedLinks)) {
                $linksJson = json_encode($processedLinks);
            }
        }
    }

    // Update the post content, file, and links
    $updateStmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, file_url = ?, file_name = ?, links = ?, updated_at = NOW() WHERE post_id = ?");
    $updateStmt->execute([$newTitle, $newContent, $fileUrl, $fileName, $linksJson, $postId]);

    echo json_encode(['success' => true, 'message' => 'Post updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update post: ' . $e->getMessage()]);
}
?>