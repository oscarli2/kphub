<?php
// Start output buffering to prevent any warnings from interfering with JSON
ob_start();

// Suppress warnings that might interfere with JSON output
error_reporting(E_ERROR | E_PARSE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and upload logger
require_once 'db.php';
require_once 'upload_logger.php';

// Ensure we always output valid JSON
function sendJsonResponse($data) {
    ob_end_clean(); // Clean any buffered output
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Function to get facility folder ID
function getFacilityFolderId($facility) {
    switch (strtoupper($facility)) {
        case 'MMKI':
            return '12iYH8uDg9Zx7TZDA9HZhSVc0vYSjbi7r';
        case 'CAPDEV':
            return '1S9KnuKJpKo5V-CT95CaILBRn78P8_yAp';
        case 'INSTI-LEGAL':
            return '1KDUMT0tDHUY4wNoJPZA-FPJaRFVB1gLp';
        case 'PUBLIC ED':
            return '1XVzrb2jh_m0jO22xo34jMEwtD7dAr_z0';
        case 'BILIRAN':
            return '1bxeTbYsbh24JmVCGvrDEVRjjCiYJ65NM';
        case 'EASTERN SAMAR':
            return '1oNfEXhc9pZfmUXLQQgN94PnpAst6T8OK';
        case 'NORTHERN SAMAR':
            return '1HLQ9yOxhvbeAuv5DOMLR2Z3jDDJjZwVt';
        case 'LEYTE':
            return '1y6z5dPyXRXgi2Yw0BQdET7cv6uzfOOyg';
        case 'SOUTHERN LEYTE':
            return '1paeMrEqoXsqjNkz92CzQS8vpBO3zw5Dh';
        case 'ORMOC':
            return '1DNBPpy57XnfJcydrRuBLZBGJiSgLTql6';
        case 'TACLOBAN':
            return '1kDXCDHAbUo8zwgbXxMJS0BUYAYnUw4IH';
        case 'LINKAGE':
            return '1kVFVPSjm9mJVPvQ4A_BfVORZmw0iga94';
        case 'SAMAR':
            return '1We0wIJMg0XURRRDWRq4QQNt6ejGJYrZG';
        default:
            return '1WqMh5IylXYHS-o_iPxImJLHczCi-WtyN';
    }
}

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Not logged in']);
}

$userId = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$title = trim($_POST['title'] ?? '');
$links = $_POST['links'] ?? null;
$postType = 'text';
$fileName = null;
$fileUrl = null;
$fileType = null;
$uploadedFiles = [];

// Handle file upload if present
if (isset($_FILES['postFile'])) {
    $files = $_FILES['postFile'];

    // Check if multiple files or single file
    if (is_array($files['name'])) {
        // Multiple files
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $fileError = $files['error'][$i];

            // Skip files with no upload error
            if ($fileError === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK) {
                // Handle upload errors
                switch ($fileError) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        sendJsonResponse(['success' => false, 'message' => 'One or more files are too large. Maximum file size is 50MB per file.']);
                    case UPLOAD_ERR_PARTIAL:
                        sendJsonResponse(['success' => false, 'message' => 'File upload was interrupted. Please try again.']);
                    case UPLOAD_ERR_NO_TMP_DIR:
                    case UPLOAD_ERR_CANT_WRITE:
                    case UPLOAD_ERR_EXTENSION:
                        sendJsonResponse(['success' => false, 'message' => 'File upload failed due to server configuration. Please contact administrator.']);
                    default:
                        sendJsonResponse(['success' => false, 'message' => 'Unknown file upload error occurred.']);
                }
            }

            // Process the file
            $uploadDir = 'uploads/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = $files['name'][$i];
            $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);

            // Sanitize filename: remove/replace problematic characters
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedBaseName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $baseName);
            $fileName = time() . '_' . $i . '_' . $sanitizedBaseName . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                $uploadedFiles[] = [
                    'original_name' => $originalName,
                    'file_name' => $fileName,
                    'file_url' =>  $filePath,
                    'file_type' => $fileExtension,
                    'file_size' => $files['size'][$i]
                ];

                // Log the file upload
                logFileUpload(
                    $userId,
                    $originalName,
                    $files['size'][$i],
                    $fileExtension,
                    $filePath,
                    'post_attachment'
                );
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to save uploaded file: ' . $originalName]);
            }
        }

        if (!empty($uploadedFiles)) {
            $postType = 'file';
            // For backward compatibility, set the first file as the main file
            $fileName = $uploadedFiles[0]['original_name'];
            $fileUrl = $uploadedFiles[0]['file_url'];
            $fileType = $uploadedFiles[0]['file_type'];
        }
    } else {
        // Single file (backward compatibility)
        $fileError = $files['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            // Handle upload errors
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    sendJsonResponse(['success' => false, 'message' => 'File is too large. Maximum file size is 50MB.']);
                case UPLOAD_ERR_PARTIAL:
                    sendJsonResponse(['success' => false, 'message' => 'File upload was interrupted. Please try again.']);
                case UPLOAD_ERR_NO_FILE:
                    // No file uploaded, continue with text post
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    sendJsonResponse(['success' => false, 'message' => 'File upload failed due to server configuration. Please contact administrator.']);
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Unknown file upload error occurred.']);
            }
        } else {
            // File uploaded successfully, process it
            $uploadDir = 'uploads/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = $files['name'];
            $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);

            // Sanitize filename: remove/replace problematic characters
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedBaseName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $baseName);
            $fileName = time() . '_' . $sanitizedBaseName . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($files['tmp_name'], $filePath)) {
                $uploadedFiles[] = [
                    'original_name' => $originalName,
                    'file_name' => $fileName,
                    'file_url' => $filePath,
                    'file_type' => $fileExtension,
                    'file_size' => $files['size']
                ];

                $postType = 'file';

                // Log the file upload
                logFileUpload(
                    $userId,
                    $originalName,
                    $files['size'],
                    $fileExtension,
                    $filePath,
                    'post_attachment'
                );
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to save uploaded file.']);
            }
        }
    }
}

// Validate post has content and title
if (empty($title)) {
    sendJsonResponse(['success' => false, 'message' => 'Post title cannot be empty']);
}

if (empty($content) && $postType === 'text') {
    sendJsonResponse(['success' => false, 'message' => 'Post content cannot be empty']);
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
        $pdo->exec("ALTER TABLE posts ADD COLUMN links TEXT NULL AFTER file_type");
    }

    // Process links if provided
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

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, post_type, content, file_name, file_url, file_type, links) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $postType, $content, $fileName, $fileUrl, $fileType, $linksJson]);

    $postId = $pdo->lastInsertId();

    // Save multiple attachments if any
    if (!empty($uploadedFiles)) {
        $attachmentStmt = $pdo->prepare("INSERT INTO post_attachments (post_id, file_name, file_url, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        foreach ($uploadedFiles as $file) {
            $attachmentStmt->execute([
                $postId,
                $file['original_name'],
                $file['file_url'],
                $file['file_type'],
                $file['file_size']
            ]);
        }
    }
    
    // Create notifications for all users except the poster
    $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id != ?");
    $userStmt->execute([$userId]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get poster's facility
    $posterStmt = $pdo->prepare("SELECT facility FROM users WHERE user_id = ?");
    $posterStmt->execute([$userId]);
    $posterFacility = $posterStmt->fetch()['facility'];
    
    foreach ($users as $user) {
        $message = $posterFacility . " shared a new post";
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, post_id, from_user_id) VALUES (?, 'new_post', ?, ?, ?)");
        $notifStmt->execute([$user['user_id'], $message, $postId, $userId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Post created successfully']);
} catch (PDOException $e) {
    error_log("Create post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create post']);
}

// Flush output buffer to send the JSON response
ob_end_flush();
?>