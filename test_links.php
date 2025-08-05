<?php
require_once 'config/config.php';

echo "<h2>Link Test</h2>";

echo "<h3>Current User Status:</h3>";
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
} else {
    echo "<p><strong>You are not logged in!</strong></p>";
    echo "<p><a href='auth/login.php'>Click here to login</a></p>";
}

echo "<h3>Test Links (with target='_blank'):</h3>";
echo "<a href='teams/register.php' target='_blank'>Test Team Registration</a><br>";
echo "<a href='players/register.php' target='_blank'>Test Player Registration</a><br>";
echo "<a href='coaches/register.php' target='_blank'>Test Coach Registration</a><br>";

echo "<h3>Admin Page Links:</h3>";
echo "<a href='admin/teams.php' target='_blank'>Admin Teams Page</a><br>";
echo "<a href='admin/players.php' target='_blank'>Admin Players Page</a><br>";
echo "<a href='admin/coaches.php' target='_blank'>Admin Coaches Page</a><br>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?> 