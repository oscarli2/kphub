<?php
// Test script to simulate post creation with file upload
session_start();
require_once 'db.php';
require_once 'session.php'; // This should set up the session

// Simulate a logged-in user (replace with actual user ID)
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists

// Simulate POST data
$_POST['title'] = 'Test Post with File';
$_POST['content'] = 'This is a test post content';

// Simulate file upload
$_FILES['postFile'] = [
    'name' => ['test_file.txt'],
    'type' => ['text/plain'],
    'tmp_name' => [tempnam(sys_get_temp_dir(), 'test')],
    'error' => [UPLOAD_ERR_OK],
    'size' => [1024]
];

// Create a test file
file_put_contents($_FILES['postFile']['tmp_name'][0], 'This is test file content');

echo "Simulating post creation with file upload...\n";

// Include the create_post.php logic (without the header/json output)
include 'create_post.php';

echo "Test completed.\n";
?>