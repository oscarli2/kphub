<?php
require_once 'page_security.php';

PageSecurity::initPageSecurity();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$_GET['newsfeed_mode'] = 'manage';
require 'index.php';