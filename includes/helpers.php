<?php
// wavinyacup/includes/helpers.php

// Ensure a session is started before using session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file to use the db() function
require_once __DIR__ . '/db.php';

// Sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// CSRF Protection
function generate_csrf_token() {
    // You must define the constant CSRF_TOKEN_NAME in your config.php file
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

// Logger
function log_activity($user_id, $action, $description = '') {
    // Get the database instance
    $db = db();
    try {
        $db->query(
            "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$user_id, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    } catch (Exception $e) {
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

// Authentication Helpers

/**
 * Checks if a user is logged in by verifying the presence of a user ID in the session.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific permission.
 * This is a basic implementation and can be expanded for more complex roles.
 * @param string $permission The permission to check for (e.g., 'manage_players').
 * @return bool
 */
function has_permission($permission) {
    // If the user isn't logged in, they can't have permissions.
    if (!is_logged_in()) {
        return false;
    }

    // A simple role-based check from the session.
    $user_role = $_SESSION['user_role'] ?? 'guest';

    // Permission mapping: Define which roles have access to which features.
    $permissions_map = [
        'admin' => ['manage_players', 'manage_teams', 'view_dashboard', 'manage_tournaments', 'manage_users', 'manage_coaches', 'approve_coaches'],
        'coach' => ['manage_own_team', 'manage_own_players', 'view_own_dashboard'],
        'captain' => ['manage_players', 'register_players'],
        'player' => [] // Players have no special management permissions.
    ];

    return in_array($permission, $permissions_map[$user_role] ?? []);
}

/**
 * Checks if the logged-in user has a specific role.
 * @param string $role The role to check for (e.g., 'admin', 'captain').
 * @return bool
 */
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    return ($_SESSION['user_role'] ?? 'guest') === $role;
}

/**
 * Retrieves the current user's full data from the database.
 * @return array|null An associative array of user data or null if not logged in.
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }

    $db = db();
    $user_id = $_SESSION['user_id'];
    return $db->fetchRow("SELECT * FROM users WHERE id = ?", [$user_id]);
}

/**
 * Generates a unique team code based on the ward code.
 * @param string $ward_code The code of the ward.
 * @return string The generated team code.
 */
function generate_team_code($ward_code) {
    global $db; // Assuming $db is a global variable from config.php

    // Get the latest team ID to create a sequential suffix
    $last_id = $db->fetchRow("SELECT MAX(id) as max_id FROM teams");
    $next_id = ($last_id['max_id'] ?? 0) + 1;
    
    // Create a unique, zero-padded suffix
    $suffix = str_pad($next_id, 3, '0', STR_PAD_LEFT);
    
    return strtoupper($ward_code . '-' . $suffix);
}

/**
 * Check if coach owns a specific team
 * @param int $coach_user_id The coach's user ID
 * @param int $team_id The team ID to check
 * @return bool
 */
function coach_owns_team($coach_user_id, $team_id) {
    $db = db();
    $coach = $db->fetchRow("SELECT team_id FROM coaches WHERE user_id = ?", [$coach_user_id]);
    return $coach && $coach['team_id'] == $team_id;
}

/**
 * Get coach's team ID
 * @param int $coach_user_id The coach's user ID
 * @return int|null
 */
function get_coach_team_id($coach_user_id) {
    $db = db();
    $coach = $db->fetchRow("SELECT team_id FROM coaches WHERE user_id = ?", [$coach_user_id]);
    return $coach ? $coach['team_id'] : null;
}