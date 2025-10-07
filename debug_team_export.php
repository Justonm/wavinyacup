<?php
// Debug script to check team ID and export functionality
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

if (!is_logged_in()) {
    die('Please log in first.');
}

$db = db();
$user = get_logged_in_user();

echo "<h2>Debug Team Export Issue</h2>";

// Get coach info
$coach = $db->fetchRow("
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, t.ward_id, t.team_photo, w.name as ward_name
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    WHERE c.user_id = ?
", [$user['id']]);

echo "<h3>Current User Info:</h3>";
echo "User ID: " . $user['id'] . "<br>";
echo "Role: " . $user['role'] . "<br>";

echo "<h3>Coach Info:</h3>";
if ($coach) {
    echo "Coach found: Yes<br>";
    echo "Team ID: " . ($coach['team_id'] ?? 'NULL') . "<br>";
    echo "Team Name: " . ($coach['team_name'] ?? 'NULL') . "<br>";
    echo "Team Code: " . ($coach['team_code'] ?? 'NULL') . "<br>";
} else {
    echo "Coach found: No<br>";
}

// Check if team exists in database
if ($coach && $coach['team_id']) {
    $team_check = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$coach['team_id']]);
    echo "<h3>Team Database Check:</h3>";
    if ($team_check) {
        echo "Team exists in database: Yes<br>";
        echo "Team ID: " . $team_check['id'] . "<br>";
        echo "Team Name: " . $team_check['name'] . "<br>";
        echo "Ward ID: " . $team_check['ward_id'] . "<br>";
    } else {
        echo "Team exists in database: No<br>";
    }
    
    // Check export URL
    $export_url = "viewer/export_team_pdf.php?id=" . $coach['team_id'];
    echo "<h3>Export URL:</h3>";
    echo "URL: <a href='{$export_url}' target='_blank'>{$export_url}</a><br>";
}

// List all teams for reference
echo "<h3>All Teams in Database:</h3>";
$all_teams = $db->fetchAll("SELECT id, name, team_code FROM teams ORDER BY id");
foreach ($all_teams as $team) {
    echo "ID: {$team['id']}, Name: {$team['name']}, Code: {$team['team_code']}<br>";
}
?>
