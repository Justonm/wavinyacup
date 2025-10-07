<?php
// debug_session.php - Check current session data
session_start();

if (!isset($_GET['check']) || $_GET['check'] !== 'session2025') {
    die('Access denied');
}

echo "<h2>Session Debug Information</h2>";

echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>Authentication Check:</h3>";
echo "user_id set: " . (isset($_SESSION['user_id']) ? "YES (" . $_SESSION['user_id'] . ")" : "NO") . "<br>";
echo "role set: " . (isset($_SESSION['role']) ? "YES (" . $_SESSION['role'] . ")" : "NO") . "<br>";
echo "username set: " . (isset($_SESSION['username']) ? "YES (" . $_SESSION['username'] . ")" : "NO") . "<br>";

echo "<h3>Expected for Admin Access:</h3>";
echo "• user_id should be set<br>";
echo "• role should be 'admin'<br>";

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo "<p style='color: green;'><strong>✅ Session looks good for admin access</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Session not valid for admin access</strong></p>";
    echo "<p>You may need to log out and log back in as admin.</p>";
}
?>
