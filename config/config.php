<?php
/**
 * Main Configuration File for Machakos County Team Registration System
 */

// ==== Load Environment Variables ====
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // Loads .env if exists (doesn’t crash if missing)

// ==== Application Info ====
define('APP_NAME', 'Machakos County Team Registration System');
define('APP_VERSION', '1.0.0');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost'); // Update in production
define('APP_EMAIL', $_ENV['APP_EMAIL'] ?? 'info@machakoscounty.go.ke');

// ==== Session Settings ====
define('SESSION_NAME', 'machakos_teams_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// ==== Fix Session Permission Issue (for local dev) ====
$customSessionPath = __DIR__ . '/../sessions';
if (!is_dir($customSessionPath)) {
    mkdir($customSessionPath, 0700, true);
}

// ==== Start Session ====
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', $customSessionPath);
    session_name(SESSION_NAME);
    session_start();
}

// ==== Security Settings ====
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_COST', 12); // bcrypt cost factor

// ==== File Upload Settings ====
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// ==== Pagination ====
define('ITEMS_PER_PAGE', 20);

// ==== Email Settings ====
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');

// ==== Timezone ====
date_default_timezone_set('Africa/Nairobi');

// ==== Error Reporting ====
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ==== Include DB Config ====
require_once __DIR__ . '/database.php';

// ==== Helper Functions ====

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
function redirect($url) {
    header("Location: $url");
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

function has_role($role) {
    $user = get_logged_in_user();
    return $user && $user['role'] === $role;
}

function has_permission($permission) {
    $user = get_logged_in_user();
    if (!$user) return false;

    $permissions = [
        'admin' => ['all'],
        'county_admin' => ['manage_teams', 'manage_players', 'view_reports', 'approve_registrations'],
        'sub_county_admin' => ['manage_teams', 'manage_players', 'view_reports'],
        'ward_admin' => ['manage_teams', 'view_reports'],
        'coach' => ['manage_team', 'manage_players'],
        'captain' => ['view_team', 'view_players'],
        'player' => ['view_profile']
    ];

    return isset($permissions[$user['role']]) &&
           (in_array('all', $permissions[$user['role']]) ||
            in_array($permission, $permissions[$user['role']]));
}

// Formatters
function format_date($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($datetime));
}

function generate_team_code($ward_code) {
    return $ward_code . time() . rand(1000, 9999);
}

// Validators
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(254|0)?[17]\d{8}$/', $clean);
}

// Logger
function log_activity($user_id, $action, $details = '') {
    db()->query(
        "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
        [$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
    );
}
