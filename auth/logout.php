<?php
require_once '../config/config.php';

// Log the logout activity
if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
redirect('login.php');
?> 