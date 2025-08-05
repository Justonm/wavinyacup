<?php
require_once 'config/config.php';

echo "<h3>Team Registration Test</h3>";

// Test with a unique team name
$team_name = 'KDO Stars Test ' . time();
$ward_id = 1;
$founded_year = 2024;
$home_ground = 'Test Ground';
$team_colors = 'Red and White';

try {
    $db = db();
    
    // Generate unique team code
    $ward = $db->fetchRow("SELECT code FROM wards WHERE id = ?", [$ward_id]);
    $team_code = $ward['code'] . time() . rand(1000, 9999);
    
    echo "Testing team: $team_name<br>";
    echo "Team code: $team_code<br>";
    
    // Check if team code already exists
    $existing = $db->fetchRow("SELECT id FROM teams WHERE team_code = ?", [$team_code]);
    if ($existing) {
        echo "❌ Team code already exists!<br>";
    } else {
        echo "✅ Team code is unique<br>";
    }
    
    // Insert team
    $result = $db->query("
        INSERT INTO teams (name, ward_id, coach_id, team_code, founded_year, home_ground, team_colors) 
        VALUES (?, ?, 1, ?, ?, ?, ?)
    ", [$team_name, $ward_id, $team_code, $founded_year, $home_ground, $team_colors]);
    
    if ($result) {
        $team_id = $db->lastInsertId();
        echo "✅ Team inserted successfully! ID: $team_id<br>";
        
        // Create registration
        $db->query("
            INSERT INTO team_registrations (team_id, season_year, registration_date) 
            VALUES (?, ?, CURDATE())
        ", [$team_id, date('Y')]);
        
        echo "✅ Team registration created!<br>";
        
        // Verify
        $total_teams = $db->fetchRow("SELECT COUNT(*) as count FROM teams")['count'];
        echo "✅ Total teams in database: $total_teams<br>";
        
    } else {
        echo "❌ Team insertion failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='admin/dashboard.php'>Check Dashboard</a>";
?> 