<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

$db = db();
$team_id = 26; // Kiboko Yenu team

echo "<h2>Testing Player Registration for Team ID: $team_id</h2>";

try {
    // Check team exists
    $team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);
    if (!$team) {
        die("Team not found!");
    }
    echo "<p>✅ Team: " . htmlspecialchars($team['name']) . "</p>";

    // Check current player count
    $current_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
    echo "<p>Current active players: $current_count</p>";

    // Show all players for this team
    $players = $db->fetchAll("SELECT id, first_name, last_name, is_active, created_at FROM players WHERE team_id = ? ORDER BY created_at DESC", [$team_id]);
    echo "<h3>All Players for this team:</h3>";
    if (empty($players)) {
        echo "<p>❌ No players found</p>";
    } else {
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
    }

    // Test a manual insert to see if it works
    echo "<h3>Testing Manual Insert:</h3>";
    
    $db->beginTransaction();
    
    try {
        // Create test user with unique ID number
        $test_username = 'test.player.' . time();
        $unique_id_number = 'TEST' . time(); // Generate unique ID number
        echo "<p>Attempting to insert user: $test_username with ID: $unique_id_number</p>";
        
        $db->query("INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number) VALUES (?, ?, ?, 'player', ?, ?, ?, ?)", 
            [$test_username, 'test' . time() . '@example.com', password_hash('test123', PASSWORD_DEFAULT), 'Test', 'Player', '0700000000', $unique_id_number]);
        
        $user_id = $db->lastInsertId();
        echo "<p>✅ Created test user with ID: $user_id</p>";
        
        if ($user_id == 0) {
            throw new Exception("User insert failed - lastInsertId returned 0");
        }
        
        // Insert test player
        echo "<p>Attempting to insert player for user ID: $user_id</p>";
        $db->query("INSERT INTO players (user_id, team_id, first_name, last_name, gender, date_of_birth, position, jersey_number, height_cm, weight_kg, preferred_foot, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
            [$user_id, $team_id, 'Test', 'Player', 'male', '1995-01-01', 'midfielder', 10, 175, 70.5, 'right', 1, 1]);
        
        $player_id = $db->lastInsertId();
        echo "<p>✅ Created test player with ID: $player_id</p>";
        
        if ($player_id == 0) {
            throw new Exception("Player insert failed - lastInsertId returned 0");
        }
        
        $db->commit();
        echo "<p>✅ Transaction committed successfully</p>";
        
    } catch (Exception $insert_e) {
        $db->rollBack();
        echo "<p>❌ Insert failed: " . $insert_e->getMessage() . "</p>";
        $user_id = 0;
        $player_id = 0;
    }
    
    // Check count again
    $new_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
    echo "<p>New active player count: $new_count</p>";
    
    // Clean up test data
    $db->query("DELETE FROM players WHERE id = ?", [$player_id]);
    $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
    echo "<p>✅ Cleaned up test data</p>";
    
} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->rollBack();
    }
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
