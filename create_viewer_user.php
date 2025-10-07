<?php
// create_viewer_user.php

// This script should only be run once from the command line or by an admin.
// For security, it's recommended to delete this file after use.

// This script is now self-contained to avoid external configuration issues.
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// --- Database Connection ---
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? '';
$db_user = $_ENV['DB_USER'] ?? '';
$db_pass = $_ENV['DB_PASS'] ?? '';
$db_charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

if (empty($db_name) || empty($db_user)) {
    die("Database credentials are not set in your .env file. Please check the file.");
}

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("🔴 Database connection failed: " . $e->getMessage() . "\n");
}

// User details
$email = 'user@governorwavinyacup.com';
$password = '2025.GvN@WaV!@3Rd';
$role = 'viewer';
$username = 'viewer_user';
$first_name = 'Viewer';
$last_name = 'User';

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        echo "✅ User with email '{$email}' or username '{$username}' already exists. No action taken.\n";
        exit;
    }

    // Insert the new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role, first_name, last_name, approval_status, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 'approved', 1)
    ");
    $stmt->execute([$username, $email, $password_hash, $role, $first_name, $last_name]);

    $user_id = $pdo->lastInsertId();

    echo "Successfully created 'viewer' user with ID: {$user_id}\n";
    echo "Email: {$email}\n";
    echo "Password: [REDACTED]\n";

} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}
