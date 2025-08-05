<?php
require_once 'config/config.php';

echo "<h3>Quick Database Test</h3>";

try {
    $db = db();
    $team_count = $db->fetchRow("SELECT COUNT(*) as count FROM teams")['count'];
    echo "✅ Database connected. Total teams: $team_count<br>";
    
    $active_teams = $db->fetchRow("SELECT COUNT(*) as count FROM teams WHERE status = 'active'")['count'];
    echo "✅ Active teams: $active_teams<br>";
    
    if ($team_count > 0) {
        $teams = $db->fetchAll("SELECT name, created_at FROM teams ORDER BY created_at DESC LIMIT 3");
        echo "✅ Recent teams:<br>";
        foreach ($teams as $team) {
            echo "- " . $team['name'] . " (created: " . $team['created_at'] . ")<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?> 