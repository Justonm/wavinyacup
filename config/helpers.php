<?php
// wavinyacup/includes/helpers.php

// Include the database connection file to use the db() function
require_once __DIR__ . '/db.php';

// Sanitize input. Note: htmlspecialchars should be used at output, not input
function sanitize_input($data) {
    // Trim whitespace, and remove backslashes.
    return trim(stripslashes($data));
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
    // The hash_equals() function is a timing-attack safe string comparison
    // Use it to prevent an attacker from guessing the token character by character.
    return isset($_SESSION[CSRF_TOKEN_NAME]) && is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Redirect helper
function redirect($url, $http_code = 302) {
    header("Location: " . $url, true, $http_code);
    exit();
}

/**
 * Gets the application's base URL using the APP_URL constant defined in config.php.
 * This function was missing and is crucial for the redirect on view_coaches.php.
 * @return string The application base URL.
 */
if (!function_exists('app_base_url')) {
    function app_base_url() {
        // APP_URL is defined in config.php.
        return defined('APP_URL') ? rtrim(APP_URL, '/') : '/'; 
    }
}

// Logger
function log_activity($user_id, $action, $description = '') {
    // Get the database instance
    $db = db();
    try {
        $db->query(
            "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$user_id, $action, $description, $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent']
        );
    } catch (Exception $e) {
        // Log to the PHP error log
        error_log("Activity Log Failed: User $user_id - $action - $description | Error: " . $e->getMessage());
    }
}

// Formatters
function format_date($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return 'N/A';
    }
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        error_log("Date formatting failed for date '$date': " . $e->getMessage());
        return 'Invalid Date';
    }
}

// Validators
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_phone($phone) {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    // Kenyan phone number validation
    return preg_match('/^(254|0)?[17]\d{8}$/', $clean) === 1;
}

// Authentication Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function has_permission($permission) {
    if (!is_logged_in()) {
        return false;
    }

    $user_role = $_SESSION['user_role'] ?? 'guest';

    $permissions_map = [
        // Added 'view_all_coaches' for viewer/admin roles
        'admin' => ['manage_players', 'manage_teams', 'view_dashboard', 'manage_tournaments', 'manage_users', 'manage_coaches', 'approve_coaches', 'view_all_coaches', 'view_all_teams', 'view_all_players'],
        'coach' => ['manage_own_team', 'manage_own_players', 'view_own_dashboard'],
        'captain' => ['manage_players', 'register_players'],
        'viewer' => ['view_dashboard', 'view_all_teams', 'view_all_players', 'export_team_pdf', 'view_all_coaches'],
        'player' => []
    ];

    return in_array($permission, $permissions_map[$user_role] ?? []);
}

function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    return ($_SESSION['user_role'] ?? 'guest') === $role;
}

function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    $db = db();
    $user_id = $_SESSION['user_id'];
    return $db->fetchRow("SELECT * FROM users WHERE id = ?", [$user_id]);
}

/**
 * Generates a unique team code based on the ward code and a random string.
 * @param int $ward_id
 * @return string
 */
function generate_team_code($ward_id) {
    global $db;
    $ward_prefix = $db->fetchCell("SELECT code FROM wards WHERE id = ?", [$ward_id]);
    if (!$ward_prefix) {
        throw new Exception("Invalid ward ID provided.");
    }
    
    $code = strtoupper($ward_prefix) . '-' . strtoupper(bin2hex(random_bytes(2)));
    // Ensure the code is unique
    while ($db->fetchCell("SELECT id FROM teams WHERE team_code = ?", [$code])) {
        $code = strtoupper($ward_prefix) . '-' . strtoupper(bin2hex(random_bytes(2)));
    }
    return $code;
}

function coach_owns_team($coach_user_id, $team_id) {
    $db = db();
    $coach = $db->fetchRow("SELECT team_id FROM coaches WHERE user_id = ?", [$coach_user_id]);
    return $coach && (int)$coach['team_id'] === (int)$team_id;
}

function get_coach_team_id($coach_user_id) {
    $db = db();
    $coach = $db->fetchRow("SELECT team_id FROM coaches WHERE user_id = ?", [$coach_user_id]);
    return $coach ? (int)$coach['team_id'] : null;
}

// Flash Message System
/**
 * Sets a flash message to be displayed on the next page load.
 * @param string $message The message to be displayed.
 * @param string $type The type of message (e.g., 'success', 'error', 'warning').
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Gets and clears the flash message.
 * @return array|null An array containing 'message' and 'type', or null if no message exists.
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Adds an email to the database queue for sending.
 * @param string $recipient Recipient's email address.
 * @param string $subject Email subject.
 * @param string $body Email body.
 * @return bool True on success, false on failure.
 */
function queue_email($recipient, $subject, $body) {
    $db = db();
    try {
        $db->query(
            "INSERT INTO email_queue (recipient, subject, body) VALUES (?, ?, ?)",
            [$recipient, $subject, $body]
        );
        return true;
    } catch (Exception $e) {
        error_log("Failed to queue email to {$recipient}: " . $e->getMessage());
        return false;
    }
}

function get_email_template($template_name, $data) {
    $template_path = __DIR__ . "/../emails/{$template_name}.php";
    if (!file_exists($template_path)) {
        return "<p>Error: Email template not found.</p>";
    }

    // Extract data for use in the template
    extract($data);

    ob_start();
    include $template_path;
    return ob_get_clean();
}

/**
 * Gets the current user's role from session
 * @return string The user's role or 'guest' if not logged in
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * Redirects user to appropriate dashboard based on their role
 * @param string $role The user's role
 */
function redirect_to_dashboard($role) {
    // Use the reliable app_base_url() now that it is defined.
    $base_url = app_base_url();
    
    switch ($role) {
        case 'admin':
            redirect($base_url . '/admin/dashboard.php');
            break;
        case 'coach':
            redirect($base_url . '/coach/dashboard.php');
            break;
        case 'captain':
            redirect($base_url . '/captain/dashboard.php');
            break;
        case 'player':
            redirect($base_url . '/player/dashboard.php');
            break;
        case 'viewer':
            redirect($base_url . '/viewer/dashboard.php');
            break;
        case 'sub_county':
            redirect($base_url . '/sub_county/dashboard.php');
            break;
        case 'ward':
            redirect($base_url . '/ward/dashboard.php');
            break;
        default:
            redirect($base_url . '/index.php');
            break;
    }
}