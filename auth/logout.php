<?php
// Include all necessary configuration and helper files
require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/permissions.php';
require_once 'gmail_oauth.php';

// Start the session to ensure it can be destroyed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Capture role before destroying the session (used for post-logout redirect)
$current_role = function_exists('get_user_role') ? get_user_role() : null;

// Log the logout activity if a user is logged in
if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Use the GmailOAuth logout method to clear the session
GmailOAuth::logout();

// Determine target login based on explicit query param or previous role
$base = app_base_url();
$to = $_GET['to'] ?? '';

if ($to === 'coach' || $current_role === 'coach') {
    redirect($base . '/auth/coach_login.php');
} elseif ($to === 'admin' || $current_role === 'admin') {
    redirect($base . '/auth/admin_login.php');
} elseif ($to === 'captain' || $current_role === 'captain') {
    redirect($base . '/auth/captain_login.php');
} else {
    // Fallback to general login
    redirect($base . '/auth/login.php');
}
?>