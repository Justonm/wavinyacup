<?php
// Live error capture for 403 debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create a custom error log for this session
$customLogFile = 'debug_403_' . date('Y-m-d_H-i-s') . '.log';

function logError($message) {
    global $customLogFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($customLogFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Log the start of debugging
logError("=== 403 Debug Session Started ===");
logError("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
logError("IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
logError("Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));

echo "<h2>Live 403 Error Capture</h2>";
echo "<p>Custom log file: <strong>$customLogFile</strong></p>";

// Test basic functionality
echo "<h3>Basic Tests:</h3>";

// Test 1: Include config
try {
    require_once 'config/config.php';
    echo "✓ Config loaded<br>";
    logError("Config loaded successfully");
} catch (Exception $e) {
    echo "✗ Config error: " . $e->getMessage() . "<br>";
    logError("Config error: " . $e->getMessage());
}

// Test 2: Database connection
try {
    require_once 'includes/db.php';
    $db = db();
    echo "✓ Database connected<br>";
    logError("Database connected successfully");
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
    logError("Database error: " . $e->getMessage());
}

// Test 3: Authentication
try {
    require_once 'includes/helpers.php';
    if (is_logged_in()) {
        $user = get_logged_in_user();
        echo "✓ User authenticated: " . $user['role'] . "<br>";
        logError("User authenticated: " . $user['role'] . " (ID: " . $user['id'] . ")");
    } else {
        echo "✗ User not authenticated<br>";
        logError("User not authenticated");
    }
} catch (Exception $e) {
    echo "✗ Auth error: " . $e->getMessage() . "<br>";
    logError("Auth error: " . $e->getMessage());
}

// Test 4: Simulate form submission
echo "<h3>Form Submission Test:</h3>";
echo '<form method="POST" action="coach/manage_team.php" target="_blank">
    <input type="hidden" name="action" value="add_player">
    <input type="text" name="first_name" value="Test" placeholder="First Name">
    <input type="text" name="last_name" value="Player" placeholder="Last Name">
    <input type="text" name="id_number" value="12345678" placeholder="ID Number">
    <select name="gender">
        <option value="male">Male</option>
    </select>
    <input type="date" name="date_of_birth" value="1990-01-01">
    <select name="position">
        <option value="forward">Forward</option>
    </select>
    <input type="number" name="jersey_number" value="99">
    <input type="checkbox" name="consent" checked> Consent
    <button type="submit">Test Add Player (Opens in new tab)</button>
</form>';

// Monitor recent errors
echo "<h3>Recent Errors:</h3>";
if (file_exists($customLogFile)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($customLogFile)) . "</pre>";
}

// Instructions
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Use the test form above to trigger the 403 error</li>";
echo "<li>Check the custom log file: <strong>$customLogFile</strong></li>";
echo "<li>Also check your main error log at the same time</li>";
echo "<li>Compare the timestamps to see what happens during the 403</li>";
echo "</ol>";

echo "<p><a href='javascript:location.reload()'>Refresh to see new errors</a></p>";

logError("=== Debug page loaded successfully ===");
?>
