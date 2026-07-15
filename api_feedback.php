<?php
header('Content-Type: application/json');
require_once 'security.php';

// Security checks
SecurityManager::checkRateLimit();
SecurityManager::validateUserAgent();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'feedback_handler.php';

// Handle POST request for submitting feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Additional rate limiting for submissions
    SecurityManager::checkRateLimit('feedback_submit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    try {
        // Validate and sanitize all inputs
        $rating = SecurityManager::validateInput($input['rating'] ?? '', 'integer');
        $category = SecurityManager::validateInput($input['category'] ?? '', 'general', 50);
        $subject = SecurityManager::validateInput($input['subject'] ?? '', 'general', 200);
        $message = SecurityManager::validateInput($input['message'] ?? '', 'general', 2000);
        
        // Validate required fields
        if (empty($rating) || empty($category) || empty($subject) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        // Validate rating range
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            exit;
        }
        
        // For non-authenticated users, validate name and email
        $name = null;
        $email = null;
        
        if (!isset($_SESSION['user_id'])) {
            $name = SecurityManager::validateInput($input['name'] ?? '', 'general', 100);
            $email = SecurityManager::validateInput($input['email'] ?? '', 'email', 100);
            
            if (empty($name) || empty($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and email are required for anonymous feedback']);
                exit;
            }
        }
    } catch (Exception $e) {
        SecurityManager::logSecurityEvent("Invalid feedback input", $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input detected']);
        exit;
    }
    
    // Prepare feedback data
    $feedbackData = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'name' => $name,
        'email' => $email,
        'rating' => (int)$rating,
        'category' => $category,
        'subject' => $subject,
        'message' => $message,
        'page' => SecurityManager::validateInput($input['page'] ?? '', 'general', 100)
    ];
    
    // Submit feedback
    if (submitFeedback($feedbackData)) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit feedback']);
    }
    exit;
}

// Handle GET requests for admin functionality
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? 'stats';

try {
    switch ($action) {
        case 'stats':
            $days = (int)($_GET['days'] ?? 30);
            $stats = getFeedbackStats($days);
            if ($stats) {
                echo json_encode(['success' => true, 'data' => $stats]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch feedback stats']);
            }
            break;
            
        case 'list':
            $filters = [
                'status' => $_GET['status'] ?? null,
                'category' => $_GET['category'] ?? null,
                'rating' => $_GET['rating'] ?? null,
                'days' => $_GET['days'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            $feedback = getFeedbackList($filters);
            if ($feedback !== false) {
                echo json_encode(['success' => true, 'data' => $feedback]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch feedback list']);
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $feedbackId = (int)$input['feedback_id'];
            $status = $input['status'];
            $adminResponse = $input['admin_response'] ?? null;
            
            if (updateFeedbackStatus($feedbackId, $status, $adminResponse)) {
                echo json_encode(['success' => true, 'message' => 'Feedback updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update feedback']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>