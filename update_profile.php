<?php
session_start();
require 'db.php';
require 'upload_logger.php';

// Function to create square thumbnail
function createSquareThumbnail($sourcePath, $targetPath, $size = 200) {
    // Get image info
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Create image resource based on type
    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) {
        return false;
    }

    // Calculate square crop dimensions
    $minSize = min($width, $height);
    $xOffset = (int)(($width - $minSize) / 2);
    $yOffset = (int)(($height - $minSize) / 2);

    // Create square canvas
    $squareImage = imagecreatetruecolor($size, $size);

    // Handle transparency for PNG/GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($squareImage, false);
        imagesavealpha($squareImage, true);
        $transparent = imagecolorallocatealpha($squareImage, 255, 255, 255, 127);
        imagefill($squareImage, 0, 0, $transparent);
    }

    // Crop and resize to square
    imagecopyresampled(
        $squareImage, $sourceImage,
        0, 0, $xOffset, $yOffset,
        $size, $size, $minSize, $minSize
    );

    // Save the thumbnail
    $success = false;
    switch ($mime) {
        case 'image/jpeg':
            $success = imagejpeg($squareImage, $targetPath, 90);
            break;
        case 'image/png':
            $success = imagepng($squareImage, $targetPath, 9);
            break;
        case 'image/gif':
            $success = imagegif($squareImage, $targetPath);
            break;
        case 'image/webp':
            $success = imagewebp($squareImage, $targetPath, 90);
            break;
    }

    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($squareImage);

    return $success;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$email = trim($_POST['email'] ?? '');
$password = !empty(trim($_POST['password'] ?? '')) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : null;
$profilePic = $_FILES['profilePic']['name'] ?? null;

// Validation
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email address is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

try {
    if ($profilePic) {
        // Debug: Log the upload attempt
        error_log("PROFILE PIC UPLOAD START: user_id=$userId, filename=$profilePic, size=" . ($_FILES['profilePic']['size'] ?? 'unknown'));

        // Validate file upload
        if ($_FILES['profilePic']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = "File upload error: " . $_FILES['profilePic']['error'];
            error_log("PROFILE PIC UPLOAD FAILED: $error_msg");
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
            exit;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['profilePic']['type'], $allowedTypes)) {
            $error_msg = "Invalid file type: " . $_FILES['profilePic']['type'];
            error_log("PROFILE PIC UPLOAD FAILED: $error_msg");
            echo json_encode(['success' => false, 'message' => 'Only image files (JPG, PNG, GIF, WebP) are allowed']);
            exit;
        }

        // Validate file size (5MB max)
        if ($_FILES['profilePic']['size'] > 5 * 1024 * 1024) {
            $error_msg = "File too large: " . $_FILES['profilePic']['size'];
            error_log("PROFILE PIC UPLOAD FAILED: $error_msg");
            echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
            exit;
        }

        $baseName = pathinfo($profilePic, PATHINFO_FILENAME);
        $extension = pathinfo($profilePic, PATHINFO_EXTENSION);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $baseName) . '.' . $extension;
        $targetPath = 'uploads/' . $sanitizedName;
        $thumbnailPath = 'uploads/thumbnails/' . $sanitizedName;

        error_log("PROFILE PIC UPLOAD: Attempting to move file to $targetPath");

        if (!move_uploaded_file($_FILES['profilePic']['tmp_name'], $targetPath)) {
            $error = error_get_last();
            $error_msg = "Failed to move uploaded file: " . ($error ? $error['message'] : 'Unknown error');
            error_log("PROFILE PIC UPLOAD FAILED: $error_msg");
            echo json_encode(['success' => false, 'message' => 'Failed to save profile picture']);
            exit;
        }

        error_log("PROFILE PIC UPLOAD: File moved successfully, creating thumbnail");

        // Create thumbnails directory if it doesn't exist
        if (!is_dir('uploads/thumbnails')) {
            mkdir('uploads/thumbnails', 0755, true);
            error_log("PROFILE PIC UPLOAD: Created thumbnails directory");
        }

        // Create square thumbnail using the cropping function
        if (createSquareThumbnail($targetPath, $thumbnailPath, 200)) {
            error_log("PROFILE PIC UPLOAD: Square thumbnail created successfully at $thumbnailPath");
        } else {
            error_log("PROFILE PIC UPLOAD WARNING: Failed to create square thumbnail, copying original");
            copy($targetPath, $thumbnailPath);
        }

        // Log the profile picture upload (use original file size)
        logFileUpload(
            $userId,
            $sanitizedName,
            $_FILES['profilePic']['size'],
            pathinfo($sanitizedName, PATHINFO_EXTENSION),
            $targetPath,
            'profile_picture'
        );

        $stmt = $pdo->prepare("UPDATE users SET email=?, profile_picture=? WHERE user_id=?");
        $params = [$email, $sanitizedName, $userId];

        if ($password) {
            $stmt = $pdo->prepare("UPDATE users SET email=?, password=?, profile_picture=? WHERE user_id=?");
            $params = [$email, $password, $sanitizedName, $userId];
        }

        $result = $stmt->execute($params);

        if ($result) {
            error_log("PROFILE PIC UPLOAD SUCCESS: Database updated with $sanitizedName");
        } else {
            error_log("PROFILE PIC UPLOAD FAILED: Database update failed");
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email=?" . ($password ? ", password=?" : "") . " WHERE user_id=?");
        $params = [$email];
        if ($password) $params[] = $password;
        $params[] = $userId;
        $stmt->execute($params);
    }

    // Update session
    $_SESSION['email'] = $email;
    if ($profilePic) {
        $_SESSION['profile_picture'] = 'uploads/thumbnails/' . $sanitizedName;
    }

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);

} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating your profile']);
}
?>
