<?php
/**
 * Update Player Restrictions Script
 * Updates the system to allow only captains to add players with 22-player limit
 */

require_once 'config/config.php';

$db = db();

try {
    echo "<h2>Updating Player Management Restrictions</h2>";
    
    // Update max players per team setting from 25 to 22
    $db->query("
        UPDATE system_settings 
        SET setting_value = '22' 
        WHERE setting_key = 'max_players_per_team'
    ");
    echo "✅ Updated maximum players per team to 22<br>";
    
    // Add new system setting for captain player management
    $existing_setting = $db->fetchRow("
        SELECT COUNT(*) as count FROM system_settings 
        WHERE setting_key = 'captain_can_add_players'
    ");
    $existing_count = $existing_setting['count'] ?? 0;
    
    if ($existing_count == 0) {
        $db->query("
            INSERT INTO system_settings (setting_key, setting_value, description) 
            VALUES ('captain_can_add_players', 'true', 'Allow captains to add players to their teams')
        ");
        echo "✅ Added captain player management setting<br>";
    } else {
        $db->query("
            UPDATE system_settings 
            SET setting_value = 'true' 
            WHERE setting_key = 'captain_can_add_players'
        ");
        echo "✅ Updated captain player management setting<br>";
    }
    
    echo "<br><strong>Summary of Changes:</strong><br>";
    echo "• Captains can now add players to their teams<br>";
    echo "• Maximum players per team reduced to 22<br>";
    echo "• Captains can only add players to their own team<br>";
    echo "• Admin can manage these restrictions in user management<br>";
    
} catch (Exception $e) {
    echo "❌ Error updating settings: " . $e->getMessage();
}
?>
