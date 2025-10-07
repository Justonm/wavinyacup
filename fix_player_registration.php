<?php
// Quick fix to redirect to correct player registration page
require_once 'config/config.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

$user = get_logged_in_user();

echo "<h2>Player Registration Fix</h2>";
echo "<p>Current user role: <strong>" . $user['role'] . "</strong></p>";

if (has_role('captain')) {
    echo "<p>As a captain, you should use the captain interface to add players.</p>";
    echo "<a href='captain/add_players.php' class='btn btn-primary'>Go to Captain Player Registration</a>";
} elseif (has_role('coach')) {
    echo "<p>As a coach, you should use the coach interface to manage your team.</p>";
    echo "<a href='coach/manage_team.php' class='btn btn-primary'>Go to Coach Team Management</a>";
} else {
    echo "<p>Your role doesn't have permission to add players.</p>";
    echo "<a href='index.php' class='btn btn-secondary'>Go to Home</a>";
}

echo "<br><br>";
echo "<h3>Quick Navigation:</h3>";
echo "<ul>";
echo "<li><a href='captain/dashboard.php'>Captain Dashboard</a></li>";
echo "<li><a href='coach/dashboard.php'>Coach Dashboard</a></li>";
echo "<li><a href='admin/dashboard.php'>Admin Dashboard</a></li>";
echo "</ul>";

// Add some styling
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; }
.btn { padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn:hover { opacity: 0.8; }
</style>";
?>
