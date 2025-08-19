<?php
/**
 * Main Entry Point - Machakos County Team Registration System
 */

// Include all necessary configuration and helper files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/permissions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

// Get user data and role from the session
// Note: We're not using get_logged_in_user() directly here to avoid a database call
// unless absolutely necessary. The session should contain the role.
$user_role = $_SESSION['user_role'] ?? null;

// Redirect based on user role
switch ($user_role) {
    case 'admin':
    case 'county_admin':
        redirect('admin/dashboard.php');
        break;
    case 'sub_county_admin':
        redirect('sub_county/dashboard.php');
        break;
    case 'ward_admin':
        redirect('ward/dashboard.php');
        break;
    case 'coach':
        redirect('coach/dashboard.php');
        break;
    case 'captain':
        redirect('captain/dashboard.php');
        break;
    case 'player':
        redirect('player/dashboard.php');
        break;
    default:
        // If the role is unknown or not set, log them out and redirect to login
        session_destroy();
        redirect('auth/login.php');
}
?>