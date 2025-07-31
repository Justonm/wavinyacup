<?php
/**
 * Main Entry Point - Machakos County Team Registration System
 */

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

$user = get_logged_in_user();
$role = $user['role'];

// Redirect based on user role
switch ($role) {
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
        redirect('auth/login.php');
}
?> 