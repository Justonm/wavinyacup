<?php
// Debug script for 403 Forbidden error
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

echo "<h2>403 Error Diagnostic</h2>";

// Check session status
echo "<h3>Session Status:</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Check user authentication
echo "<h3>User Authentication:</h3>";
if (is_logged_in()) {
    $user = get_logged_in_user();
    echo "User logged in: YES<br>";
    echo "User ID: " . $user['id'] . "<br>";
    echo "User role: " . $user['role'] . "<br>";
    echo "User name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
    
    if (isset($_SESSION['user_team_id'])) {
        echo "Team ID in session: " . $_SESSION['user_team_id'] . "<br>";
    } else {
        echo "Team ID in session: NOT SET<br>";
    }
} else {
    echo "User logged in: NO<br>";
}

// Check database connection
echo "<h3>Database Connection:</h3>";
try {
    $db = db();
    echo "Database connection: SUCCESS<br>";
    
    // Check current player count if user is logged in
    if (is_logged_in() && isset($_SESSION['user_team_id'])) {
        $team_id = $_SESSION['user_team_id'];
        $current_player_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
        echo "Current player count for team $team_id: $current_player_count<br>";
        
        // Check team details
        $team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);
        if ($team) {
            echo "Team name: " . $team['name'] . "<br>";
            echo "Team status: " . ($team['is_active'] ?? 'unknown') . "<br>";
        } else {
            echo "Team not found in database<br>";
        }
    }
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

// Check file permissions
echo "<h3>File Permissions:</h3>";
$files_to_check = [
    'captain/add_players.php',
    'coach/manage_team.php',
    'captain/dashboard.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "$file: EXISTS (permissions: " . substr(sprintf('%o', fileperms($file)), -4) . ")<br>";
    } else {
        echo "$file: NOT FOUND<br>";
    }
}

// Check server variables
echo "<h3>Server Information:</h3>";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'not set') . "<br>";

// Check upload directories
echo "<h3>Upload Directory Status:</h3>";
$upload_dirs = [
    'uploads/players/',
    'uploads/id/',
    'uploads/coaches/'
];

foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        echo "$dir: EXISTS (writable: " . (is_writable($dir) ? 'YES' : 'NO') . ")<br>";
    } else {
        echo "$dir: NOT FOUND<br>";
    }
}

// Check recent error logs if accessible
echo "<h3>Recent PHP Errors:</h3>";
$error_log_paths = [
    'error_log',
    '../error_log',
    '/tmp/php_errors.log'
];

foreach ($error_log_paths as $log_path) {
    if (file_exists($log_path) && is_readable($log_path)) {
        echo "Found error log: $log_path<br>";
        $errors = file_get_contents($log_path);
        $recent_errors = array_slice(explode("\n", $errors), -10);
        echo "<pre>" . implode("\n", $recent_errors) . "</pre>";
        break;
    }
}

echo "<hr>";
echo "<p><strong>Access this script at:</strong> yoursite.com/debug_403_error.php</p>";
echo "<p><em>Delete this file after debugging for security.</em></p>";
?>
