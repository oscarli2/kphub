<?php
// Test post creation with simulated form data
require 'db.php';

echo "Testing post creation...\n";

// Simulate session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; // Assume admin user exists

// Simulate POST data
$_POST = [
    'title' => 'Test Post',
    'content' => 'This is a test post content',
    'links' => null
];

// No files for this test
$_FILES = [];

echo "Simulating post creation with title: '{$_POST['title']}' and content: '{$_POST['content']}'\n";

// Include the create_post.php logic (simplified)
$userId = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$title = trim($_POST['title'] ?? '');
$links = $_POST['links'] ?? null;
$postType = 'text';
$fileName = null;
$fileUrl = null;
$fileType = null;
$uploadedFiles = [];

// Skip file handling for this test

// Validate post has content and title
if (empty($title)) {
    echo "❌ Title validation failed\n";
    exit;
}

if (empty($content) && $postType === 'text') {
    echo "❌ Content validation failed\n";
    exit;
}

echo "✅ Validation passed\n";

try {
    // Check if title column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'title'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Untitled Post' AFTER user_id");
        echo "✅ Added title column\n";
    }

    // Check if links column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'links'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN links TEXT NULL AFTER file_type");
        echo "✅ Added links column\n";
    }

    // Process links
    $linksJson = null;
    if ($links) {
        $linksArray = json_decode($links, true);
        if ($linksArray && is_array($linksArray)) {
            $processedLinks = [];
            foreach ($linksArray as $link) {
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

    echo "Inserting post into database...\n";
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, post_type, content, file_name, file_url, file_type, links) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$userId, $title, $postType, $content, $fileName, $fileUrl, $fileType, $linksJson]);

    if ($result) {
        $postId = $pdo->lastInsertId();
        echo "✅ Post created successfully with ID: $postId\n";

        // Test notification creation
        $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id != ?");
        $userStmt->execute([$userId]);
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        $posterStmt = $pdo->prepare("SELECT facility FROM users WHERE user_id = ?");
        $posterStmt->execute([$userId]);
        $posterFacility = $posterStmt->fetch()['facility'];

        echo "Creating notifications for " . count($users) . " users...\n";
        foreach ($users as $user) {
            $message = $posterFacility . " shared a new post";
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, post_id, from_user_id) VALUES (?, 'new_post', ?, ?, ?)");
            $notifStmt->execute([$user['user_id'], $message, $postId, $userId]);
        }
        echo "✅ Notifications created\n";
    } else {
        echo "❌ Post insertion failed\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>