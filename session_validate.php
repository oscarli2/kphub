<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require 'db.php';

// This file just validates session and sets up database connection
// It doesn't output anything - used for API endpoints

if (!isset($_SESSION['user_id'])) {
    // Don't output anything here, let the calling script handle the response
    return false;
}

// Session is valid, continue execution
return true;
?>