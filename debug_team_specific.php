<?php
// Team-specific 403 error diagnostic
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (!is_logged_in() || !has_role('coach')) {
    die('Please log in as a coach to use this diagnostic tool.');
}

$user = get_logged_in_user();
$db = db();

echo "<h2>Team-Specific 403 Error Diagnostic</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.team-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
.working { border-left: 4px solid #28a745; }
.problematic { border-left: 4px solid #dc3545; }
.data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.data-table th { background-color: #f2f2f2; }
.error { color: #dc3545; }
.success { color: #28a745; }
</style>";

// Get all teams for this coach
$coach = $db->fetchRow("
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, t.ward_id
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    WHERE c.user_id = ?
", [$user['id']]);

if (!$coach || !$coach['team_id']) {
    echo "<div class='error'>No team found for this coach.</div>";
    exit;
}

echo "<h3>Current Coach Information</h3>";
echo "<div class='team-section'>";
echo "Coach: {$coach['first_name']} {$coach['last_name']}<br>";
echo "Team ID: {$coach['team_id']}<br>";
echo "Team Name: {$coach['team_name']}<br>";
echo "Team Code: {$coach['team_code']}<br>";
echo "Ward ID: {$coach['ward_id']}<br>";
echo "</div>";

// Get detailed team analysis
echo "<h3>Team Data Analysis</h3>";

// Check players for this team
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1
    ORDER BY p.id ASC
", [$coach['team_id']]);

echo "<div class='team-section'>";
echo "<h4>Player Count Analysis</h4>";
echo "Current players: " . count($players) . "/22<br>";

if (count($players) > 0) {
    echo "<table class='data-table'>";
    echo "<tr><th>#</th><th>Name</th><th>Jersey</th><th>Position</th><th>ID Number</th><th>Created</th></tr>";
    foreach ($players as $index => $player) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>{$player['first_name']} {$player['last_name']}</td>";
        echo "<td>{$player['jersey_number']}</td>";
        echo "<td>{$player['position']}</td>";
        echo "<td>{$player['id_number']}</td>";
        echo "<td>{$player['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// Check for data integrity issues
echo "<h3>Data Integrity Checks</h3>";

echo "<div class='team-section'>";
echo "<h4>Duplicate Checks</h4>";

// Check for duplicate jersey numbers
$duplicateJerseys = $db->fetchAll("
    SELECT jersey_number, COUNT(*) as count
    FROM players 
    WHERE team_id = ? AND is_active = 1 AND jersey_number IS NOT NULL
    GROUP BY jersey_number 
    HAVING count > 1
", [$coach['team_id']]);

if ($duplicateJerseys) {
    echo "<div class='error'>⚠️ Duplicate jersey numbers found:</div>";
    foreach ($duplicateJerseys as $dup) {
        echo "<div class='error'>Jersey #{$dup['jersey_number']}: {$dup['count']} players</div>";
    }
} else {
    echo "<div class='success'>✓ No duplicate jersey numbers</div>";
}

// Check for duplicate ID numbers in this team
$duplicateIds = $db->fetchAll("
    SELECT u.id_number, COUNT(*) as count
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1 AND u.id_number IS NOT NULL
    GROUP BY u.id_number 
    HAVING count > 1
", [$coach['team_id']]);

if ($duplicateIds) {
    echo "<div class='error'>⚠️ Duplicate ID numbers found:</div>";
    foreach ($duplicateIds as $dup) {
        echo "<div class='error'>ID #{$dup['id_number']}: {$dup['count']} players</div>";
    }
} else {
    echo "<div class='success'>✓ No duplicate ID numbers</div>";
}
echo "</div>";

// Check team permissions and settings
echo "<div class='team-section'>";
echo "<h4>Team Settings</h4>";
$teamDetails = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$coach['team_id']]);
if ($teamDetails) {
    echo "<table class='data-table'>";
    foreach ($teamDetails as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
}
echo "</div>";

// Test player addition simulation
echo "<h3>Player Addition Test</h3>";
echo "<div class='team-section'>";

$currentCount = count($players);
$maxPlayers = 22;

if ($currentCount >= $maxPlayers) {
    echo "<div class='error'>❌ Team is at maximum capacity ($currentCount/$maxPlayers)</div>";
} else {
    echo "<div class='success'>✓ Team can accept more players ($currentCount/$maxPlayers)</div>";
}

// Check upload directories
echo "<h4>Upload Directory Status</h4>";
$uploadDirs = [
    'uploads/players/',
    'uploads/id/',
    'uploads/coaches/'
];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? 'writable' : 'not writable';
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<div>$dir: exists, $perms, $writable</div>";
    } else {
        echo "<div class='error'>$dir: not found</div>";
    }
}
echo "</div>";

// Comparison with other teams (if coach has access to multiple teams)
echo "<h3>Comparison Analysis</h3>";
echo "<div class='team-section'>";

// Get stats for all teams to compare
$allTeamsStats = $db->fetchAll("
    SELECT t.id, t.name, t.team_code, COUNT(p.id) as player_count
    FROM teams t
    LEFT JOIN players p ON t.id = p.team_id AND p.is_active = 1
    GROUP BY t.id, t.name, t.team_code
    ORDER BY player_count DESC
    LIMIT 10
");

echo "<h4>Team Comparison (Top 10 by player count)</h4>";
echo "<table class='data-table'>";
echo "<tr><th>Team ID</th><th>Team Name</th><th>Code</th><th>Players</th><th>Status</th></tr>";

foreach ($allTeamsStats as $team) {
    $isCurrentTeam = ($team['id'] == $coach['team_id']);
    $rowClass = $isCurrentTeam ? 'style="background-color: #fff3cd;"' : '';
    
    echo "<tr $rowClass>";
    echo "<td>{$team['id']}</td>";
    echo "<td>{$team['name']}</td>";
    echo "<td>{$team['team_code']}</td>";
    echo "<td>{$team['player_count']}</td>";
    echo "<td>" . ($isCurrentTeam ? '<strong>YOUR TEAM</strong>' : 'Other') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<h3>Recommendations</h3>";
echo "<div class='team-section'>";
echo "<ol>";
echo "<li>If you see duplicate jersey numbers or ID numbers above, fix those first</li>";
echo "<li>Check if your team has any special restrictions compared to working teams</li>";
echo "<li>Try adding a player with a unique jersey number (like 88 or 77)</li>";
echo "<li>If the issue persists, the problem might be with specific player data validation</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='coach/manage_team.php'>Back to Manage Team</a></p>";
?>
