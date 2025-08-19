<?php
// Include all necessary configuration and helper files
require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/permissions.php';

// Start the session to ensure it can be destroyed
session_start();

// Log the logout activity if a user is logged in
if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
redirect('login.php');
?>