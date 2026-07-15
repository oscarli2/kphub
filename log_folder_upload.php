<?php
require_once 'security.php';

// Security checks
SecurityManager::checkRateLimit('folder_upload_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

require 'session_validate.php';
require 'upload_logger.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/folder_upload_debug.log');

header('Content-Type: application/json');

// Log the request for debugging
file_put_contents(__DIR__ . '/folder_upload_debug.log', 
    date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

if (!isset($_SESSION['user_id'])) {
    file_put_contents(__DIR__ . '/folder_upload_debug.log', 
        date('Y-m-d H:i:s') . " - No user_id in session\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    file_put_contents(__DIR__ . '/folder_upload_debug.log', 
        date('Y-m-d H:i:s') . " - Input: " . json_encode($input) . "\n", FILE_APPEND);
    
    if (!$input || !isset($input['files']) || !isset($input['folder_name'])) {
        file_put_contents(__DIR__ . '/folder_upload_debug.log', 
            date('Y-m-d H:i:s') . " - Invalid data\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }
    
    // Validate and sanitize inputs
    $folderName = SecurityManager::validateInput($input['folder_name'], 'general', 100);
    $files = $input['files'];
    
    if (!is_array($files)) {
        echo json_encode(['success' => false, 'error' => 'Files must be an array']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    file_put_contents(__DIR__ . '/folder_upload_debug.log', 
        date('Y-m-d H:i:s') . " - Processing " . count($files) . " files for user $userId\n", FILE_APPEND);
    
    // Log each uploaded file
    $loggedCount = 0;
    foreach ($files as $file) {
        // Validate file data
        if (!is_array($file) || !isset($file['name'])) {
            continue;
        }
        
        $filename = SecurityManager::validateInput($file['name'], 'filename', 255);
        $fileSize = $file['size'] ?? null;
        $mimeType = $file['type'] ?? null;
        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $result = logFileUpload(
            $userId,
            $filename,
            $fileSize,
            $fileExtension,
            "Google Drive: $folderName",
            'folder_upload'
        );
        
        if ($result) {
            $loggedCount++;
        }
        
        file_put_contents(__DIR__ . '/folder_upload_debug.log', 
            date('Y-m-d H:i:s') . " - Logged file: $filename (result: " . ($result ? 'success' : 'failed') . ")\n", FILE_APPEND);
    }
    
    echo json_encode(['success' => true, 'logged' => $loggedCount, 'total' => count($files)]);
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/folder_upload_debug.log', 
        date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>