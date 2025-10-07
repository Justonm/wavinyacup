<?php
// Database migration script to add missing columns to coach_registrations and teams tables
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = db();

try {
    echo "Starting database migration...\n";
    
    // Add missing columns to coach_registrations table
    $alterQueries = [
        "ALTER TABLE coach_registrations ADD COLUMN IF NOT EXISTS team_description TEXT NULL AFTER team_name",
        "ALTER TABLE coach_registrations ADD COLUMN IF NOT EXISTS founded_year INT NULL AFTER team_description", 
        "ALTER TABLE coach_registrations ADD COLUMN IF NOT EXISTS home_ground VARCHAR(255) NULL AFTER founded_year",
        "ALTER TABLE coach_registrations ADD COLUMN IF NOT EXISTS team_colors VARCHAR(100) NULL AFTER home_ground",
        "ALTER TABLE coach_registrations ADD COLUMN IF NOT EXISTS team_logo VARCHAR(255) NULL AFTER team_colors",
        "ALTER TABLE coach_registrations ADD COLUMN IF NOT EXISTS team_photo VARCHAR(255) NULL AFTER team_logo"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->query($query);
            echo "✓ Executed: " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "⚠ Warning: " . $e->getMessage() . "\n";
        }
    }
    
    // Add missing columns to teams table
    $teamsQueries = [
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS team_description TEXT NULL AFTER name",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS owner_name VARCHAR(100) NULL AFTER ward_id",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS owner_id_number VARCHAR(50) NULL AFTER owner_name",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS owner_phone VARCHAR(20) NULL AFTER owner_id_number",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS founded_year INT NULL AFTER owner_phone",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS home_ground VARCHAR(255) NULL AFTER founded_year",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS team_colors VARCHAR(100) NULL AFTER home_ground",
        "ALTER TABLE teams ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) NULL AFTER team_colors"
    ];
    
    foreach ($teamsQueries as $query) {
        try {
            $db->query($query);
            echo "✓ Executed: " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "⚠ Warning: " . $e->getMessage() . "\n";
        }
    }
    
    // Update existing records with default founded_year
    try {
        $db->query("UPDATE coach_registrations SET founded_year = YEAR(CURDATE()) WHERE founded_year IS NULL");
        echo "✓ Updated coach_registrations founded_year defaults\n";
    } catch (Exception $e) {
        echo "⚠ Warning updating coach_registrations: " . $e->getMessage() . "\n";
    }
    
    try {
        $db->query("UPDATE teams SET founded_year = YEAR(CURDATE()) WHERE founded_year IS NULL");
        echo "✓ Updated teams founded_year defaults\n";
    } catch (Exception $e) {
        echo "⚠ Warning updating teams: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "You can now test the coach registration form.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
