<?php
// Detailed 403 error diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>403 Error Detailed Diagnostic</h2>";

// Test basic PHP execution
echo "<h3>1. Basic PHP Test:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test file includes
echo "<h3>2. File Include Test:</h3>";
try {
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
        echo "✓ config.php loaded successfully<br>";
    } else {
        echo "✗ config.php not found<br>";
    }
    
    if (file_exists('includes/helpers.php')) {
        require_once 'includes/helpers.php';
        echo "✓ helpers.php loaded successfully<br>";
    } else {
        echo "✗ helpers.php not found<br>";
    }
} catch (Exception $e) {
    echo "✗ Include error: " . $e->getMessage() . "<br>";
}

// Test session
echo "<h3>3. Session Test:</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";

// Test authentication
echo "<h3>4. Authentication Test:</h3>";
if (function_exists('is_logged_in')) {
    if (is_logged_in()) {
        $user = get_logged_in_user();
        echo "✓ User logged in<br>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "User role: " . $user['role'] . "<br>";
        echo "User name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
    } else {
        echo "✗ User not logged in<br>";
    }
} else {
    echo "✗ Authentication functions not available<br>";
}

// Test database
echo "<h3>5. Database Test:</h3>";
try {
    if (function_exists('db')) {
        $db = db();
        echo "✓ Database connection successful<br>";
        
        // Test a simple query
        $test_query = $db->fetchColumn("SELECT COUNT(*) FROM users");
        echo "✓ Database query successful - Users count: $test_query<br>";
    } else {
        echo "✗ Database function not available<br>";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test POST data simulation
echo "<h3>6. POST Data Test:</h3>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST data received:<br>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
} else {
    echo "No POST data (GET request)<br>";
}

// Test file permissions
echo "<h3>7. File Permissions Test:</h3>";
$files_to_check = [
    'coach/manage_team.php',
    'uploads/players/',
    'uploads/id/',
    'sessions/'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $writable = is_writable($file) ? 'writable' : 'not writable';
        echo "$file: exists (permissions: $perms, $writable)<br>";
    } else {
        echo "$file: not found<br>";
    }
}

// Test server variables
echo "<h3>8. Server Environment:</h3>";
$server_vars = ['REQUEST_URI', 'HTTP_HOST', 'SERVER_SOFTWARE', 'DOCUMENT_ROOT', 'SCRIPT_NAME'];
foreach ($server_vars as $var) {
    echo "$var: " . ($_SERVER[$var] ?? 'not set') . "<br>";
}

// Test error logs
echo "<h3>9. Recent Errors:</h3>";
$error_log_paths = [
    'error_log',
    '../error_log',
    '/tmp/php_errors.log',
    ini_get('error_log')
];

$found_log = false;
foreach ($error_log_paths as $log_path) {
    if ($log_path && file_exists($log_path) && is_readable($log_path)) {
        echo "Found error log: $log_path<br>";
        $errors = file_get_contents($log_path);
        $lines = explode("\n", $errors);
        $recent_errors = array_slice($lines, -20);
        echo "<pre style='background: #f8f9fa; padding: 10px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(implode("\n", $recent_errors));
        echo "</pre>";
        $found_log = true;
        break;
    }
}

if (!$found_log) {
    echo "No accessible error logs found<br>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Check the error log output above for specific 403 errors</li>";
echo "<li>Verify your login status and role permissions</li>";
echo "<li>Try accessing: <a href='coach/manage_team.php'>coach/manage_team.php</a></li>";
echo "<li>Try accessing: <a href='captain/add_players.php'>captain/add_players.php</a></li>";
echo "</ul>";
?>
