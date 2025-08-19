<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

$db = db();

// Get team_id from URL
$team_id = $_GET['team_id'] ?? null;

if (!$team_id) {
    die("Please provide team_id in URL: ?team_id=X");
}

echo "<h2>Debug Player Count for Team ID: $team_id</h2>";

// Check if team exists
$team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);
if (!$team) {
    die("Team not found!");
}

echo "<h3>Team: " . htmlspecialchars($team['name']) . "</h3>";

// Get all players for this team
$players = $db->fetchAll("SELECT id, first_name, last_name, is_active, created_at FROM players WHERE team_id = ? ORDER BY created_at", [$team_id]);

echo "<h3>All Players in Database:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Created</th></tr>";
foreach ($players as $player) {
    echo "<tr>";
    echo "<td>" . $player['id'] . "</td>";
    echo "<td>" . htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) . "</td>";
    echo "<td>" . ($player['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . $player['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Count active players
$active_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
$total_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ?", [$team_id]);

echo "<h3>Player Counts:</h3>";
echo "<p><strong>Total Players:</strong> $total_count</p>";
echo "<p><strong>Active Players:</strong> $active_count</p>";
echo "<p><strong>Next Player Number:</strong> " . ($active_count + 1) . "</p>";

// Check recent registrations
$recent = $db->fetchAll("SELECT * FROM player_registrations WHERE team_id = ? ORDER BY registration_date DESC LIMIT 5", [$team_id]);
echo "<h3>Recent Registrations:</h3>";
echo "<table border='1'>";
echo "<tr><th>Player ID</th><th>Team ID</th><th>Registration Date</th></tr>";
foreach ($recent as $reg) {
    echo "<tr>";
    echo "<td>" . $reg['player_id'] . "</td>";
    echo "<td>" . $reg['team_id'] . "</td>";
    echo "<td>" . $reg['registration_date'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
