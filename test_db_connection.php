<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

$db = db();
$team_id = 25;

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic connection
    echo "<p>✅ Database connection successful</p>";
    
    // Check if team exists
    $team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);
    if ($team) {
        echo "<p>✅ Team found: " . htmlspecialchars($team['name']) . "</p>";
    } else {
        echo "<p>❌ Team with ID $team_id not found</p>";
        exit;
    }
    
    // Get all players for this team
    $all_players = $db->fetchAll("SELECT * FROM players WHERE team_id = ? ORDER BY created_at DESC", [$team_id]);
    echo "<h3>All Players (Total: " . count($all_players) . ")</h3>";
    
    if (empty($all_players)) {
        echo "<p>❌ No players found for team ID $team_id</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Created</th></tr>";
        foreach ($all_players as $player) {
            echo "<tr>";
            echo "<td>" . $player['id'] . "</td>";
            echo "<td>" . htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) . "</td>";
            echo "<td>" . ($player['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $player['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the exact query used in the registration form
    $count_query = "SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1";
    $active_count = $db->fetchColumn($count_query, [$team_id]);
    echo "<h3>Active Player Count Query Result: $active_count</h3>";
    
    // Check users table for players
    $users = $db->fetchAll("SELECT * FROM users WHERE role = 'player' ORDER BY created_at DESC LIMIT 5", []);
    echo "<h3>Recent Player Users (Total: " . count($users) . ")</h3>";
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
