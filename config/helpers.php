<?php
// wavinyacup/includes/helpers.php

// Sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// CSRF Protection
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Redirect helper
function redirect($url, $http_code = 302) {
    header("Location: " . $url, true, $http_code);
    exit();
}

// Auth Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_logged_in_user() {
    if (!is_logged_in()) return null;
    return db()->fetchRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function get_current_user_data() {
    return get_logged_in_user();
}

function has_role($role) {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Logger
function log_activity($user_id, $action, $description = '') {
    global $db;
    try {
        $db->query(
            "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$user_id, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    } catch (Exception $e) {
        // Fallback to error_log if database logging fails
        error_log("Activity Log Failed: User $user_id - $action - $description | Error: " . $e->getMessage());
    }
}

// Formatters
function format_date($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

// Validators
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(254|0)?[17]\d{8}$/', $clean);
}