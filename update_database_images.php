<?php
/**
 * Database Update Script for Image Fields
 * Run this script to add image fields to existing tables
 */

require_once 'config/config.php';

echo "Starting database update for image fields...\n";

try {
    $db = db();
    
    // Check if fields already exist
    $tables = ['users', 'players', 'coaches', 'teams'];
    $fields = ['profile_image', 'player_image', 'coach_image', 'team_photo'];
    
    foreach ($tables as $index => $table) {
        $field = $fields[$index];
        
        // Check if field exists
        $result = $db->fetchRow("SHOW COLUMNS FROM $table LIKE '$field'");
        
        if (!$result) {
            echo "Adding $field field to $table table...\n";
            
            switch ($table) {
                case 'users':
                    $db->query("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) AFTER phone");
                    break;
                case 'players':
                    $db->query("ALTER TABLE players ADD COLUMN player_image VARCHAR(255) AFTER preferred_foot");
                    break;
                case 'coaches':
                    $db->query("ALTER TABLE coaches ADD COLUMN coach_image VARCHAR(255) AFTER specialization");
                    break;
                case 'teams':
                    $db->query("ALTER TABLE teams ADD COLUMN team_photo VARCHAR(255) AFTER logo_path");
                    break;
            }
            
            echo "✓ Added $field field to $table table\n";
        } else {
            echo "✓ Field $field already exists in $table table\n";
        }
    }
    
    echo "\nDatabase update completed successfully!\n";
    echo "Image upload functionality is now ready for testing.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 