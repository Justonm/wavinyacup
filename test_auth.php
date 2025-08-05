<?php
require_once 'config/config.php';

echo "<h2>Authentication Test</h2>";

echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>User Status:</h3>";
echo "Is Logged In: " . (is_logged_in() ? "YES" : "NO") . "<br>";

if (is_logged_in()) {
    $user = get_logged_in_user();
    echo "User ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    
    echo "<h3>Permissions:</h3>";
    echo "Has manage_teams: " . (has_permission('manage_teams') ? "YES" : "NO") . "<br>";
    echo "Has manage_players: " . (has_permission('manage_players') ? "YES" : "NO") . "<br>";
    echo "Has manage_coaches: " . (has_permission('manage_coaches') ? "YES" : "NO") . "<br>";
    echo "Has all permissions: " . (has_permission('all') ? "YES" : "NO") . "<br>";
} else {
    echo "<p><strong>You are not logged in!</strong></p>";
    echo "<p><a href='auth/login.php'>Click here to login</a></p>";
}

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Links:</h3>";
echo "<a href='teams/register.php'>Test Team Registration</a><br>";
echo "<a href='players/register.php'>Test Player Registration</a><br>";
echo "<a href='coaches/register.php'>Test Coach Registration</a><br>";
?> 