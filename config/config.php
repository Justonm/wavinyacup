<?php
/**
 * Main Configuration File for Wavinya Cup
 *
 * This file defines all the core constants and settings for the application.
 */

// ==== Load Environment Variables ====
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // Loads .env if it exists, doesn't crash if missing.

// ==== Application Info ====
define('APP_NAME', 'Wavinya Cup');
define('APP_VERSION', '1.0.0');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('APP_EMAIL', $_ENV['APP_EMAIL'] ?? 'info@wavinyacup.go.ke');

// ==== Define Absolute Root Path ====
define('ROOT_PATH', dirname(__DIR__));

// ==== Session Settings ====
define('SESSION_NAME', 'wavinyacup_session');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// ==== Fix Session Permission Issue (for local dev) ====
$customSessionPath = ROOT_PATH . '/sessions';
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
define('UPLOAD_DIR', ROOT_PATH . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// ==== Pagination ====
define('ITEMS_PER_PAGE', 20);

// ==== Email Settings (using mailgun for example) ====
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.mailgun.org');
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

// ==== Database Configuration ====
require_once __DIR__ . '/database.php';