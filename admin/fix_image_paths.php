<?php
/**
 * Fix Image Paths Migration Script
 * This script fixes inconsistent image paths in the database
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user is logged in and has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/admin_login.php');
}

$db = db();
$fixes_applied = [];
$errors = [];

echo "<h2>Image Path Migration Script</h2>\n";
echo "<p>This script will fix inconsistent image paths in the database.</p>\n";

// Function to move files from one directory to another
function move_file_to_correct_directory($old_path, $new_path) {
    $old_full_path = ROOT_PATH . '/' . $old_path;
    $new_full_path = ROOT_PATH . '/' . $new_path;
    
    // Create destination directory if it doesn't exist
    $new_dir = dirname($new_full_path);
    if (!is_dir($new_dir)) {
        mkdir($new_dir, 0755, true);
    }
    
    // Move file if it exists
    if (file_exists($old_full_path) && !file_exists($new_full_path)) {
        return rename($old_full_path, $new_full_path);
    }
    
    return file_exists($new_full_path); // Return true if destination already exists
}

// Fix players with inconsistent paths
echo "<h3>Fixing Player Image Paths</h3>\n";

// Get all players with images
$players = $db->fetchAll("
    SELECT id, first_name, last_name, player_image, id_photo_front, id_photo_back 
    FROM players 
    WHERE player_image IS NOT NULL OR id_photo_front IS NOT NULL OR id_photo_back IS NOT NULL
");

foreach ($players as $player) {
    $player_name = $player['first_name'] . ' ' . $player['last_name'];
    
    // Fix player_image paths (should be in uploads/player/)
    if ($player['player_image']) {
        $current_path = $player['player_image'];
        
        // If path starts with uploads/players/, move to uploads/player/
        if (strpos($current_path, 'uploads/players/') === 0) {
            $filename = basename($current_path);
            $new_path = 'uploads/player/' . $filename;
            
            if (move_file_to_correct_directory($current_path, $new_path)) {
                $db->query("UPDATE players SET player_image = ? WHERE id = ?", [$new_path, $player['id']]);
                $fixes_applied[] = "Player photo: $player_name - moved from $current_path to $new_path";
            } else {
                $errors[] = "Failed to move player photo for $player_name from $current_path to $new_path";
            }
        }
    }
    
    // Fix ID photo paths (should be in uploads/id/)
    if ($player['id_photo_front']) {
        $current_path = $player['id_photo_front'];
        
        // If path starts with uploads/players/, move to uploads/id/
        if (strpos($current_path, 'uploads/players/') === 0) {
            $filename = basename($current_path);
            $new_path = 'uploads/id/' . $filename;
            
            if (move_file_to_correct_directory($current_path, $new_path)) {
                $db->query("UPDATE players SET id_photo_front = ? WHERE id = ?", [$new_path, $player['id']]);
                $fixes_applied[] = "ID Front photo: $player_name - moved from $current_path to $new_path";
            } else {
                $errors[] = "Failed to move ID front photo for $player_name from $current_path to $new_path";
            }
        }
    }
    
    if ($player['id_photo_back']) {
        $current_path = $player['id_photo_back'];
        
        // If path starts with uploads/players/, move to uploads/id/
        if (strpos($current_path, 'uploads/players/') === 0) {
            $filename = basename($current_path);
            $new_path = 'uploads/id/' . $filename;
            
            if (move_file_to_correct_directory($current_path, $new_path)) {
                $db->query("UPDATE players SET id_photo_back = ? WHERE id = ?", [$new_path, $player['id']]);
                $fixes_applied[] = "ID Back photo: $player_name - moved from $current_path to $new_path";
            } else {
                $errors[] = "Failed to move ID back photo for $player_name from $current_path to $new_path";
            }
        }
    }
}

// Fix teams with inconsistent paths
echo "<h3>Fixing Team Image Paths</h3>\n";

$teams = $db->fetchAll("
    SELECT id, name, team_photo 
    FROM teams 
    WHERE team_photo IS NOT NULL AND team_photo != ''
");

foreach ($teams as $team) {
    if ($team['team_photo']) {
        $current_path = $team['team_photo'];
        
        // If path doesn't start with uploads/teams/, fix it
        if (strpos($current_path, 'uploads/teams/') !== 0 && strpos($current_path, 'uploads/team/') === 0) {
            $filename = basename($current_path);
            $new_path = 'uploads/teams/' . $filename;
            
            if (move_file_to_correct_directory($current_path, $new_path)) {
                $db->query("UPDATE teams SET team_photo = ? WHERE id = ?", [$new_path, $team['id']]);
                $fixes_applied[] = "Team photo: {$team['name']} - moved from $current_path to $new_path";
            } else {
                $errors[] = "Failed to move team photo for {$team['name']} from $current_path to $new_path";
            }
        }
    }
}

// Fix coaches with inconsistent paths
echo "<h3>Fixing Coach Image Paths</h3>\n";

$coaches = $db->fetchAll("
    SELECT c.id, u.first_name, u.last_name, c.coach_image 
    FROM coaches c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.coach_image IS NOT NULL AND c.coach_image != ''
");

foreach ($coaches as $coach) {
    if ($coach['coach_image']) {
        $current_path = $coach['coach_image'];
        $coach_name = $coach['first_name'] . ' ' . $coach['last_name'];
        
        // If path doesn't start with uploads/coaches/, fix it
        if (strpos($current_path, 'uploads/coaches/') !== 0 && strpos($current_path, 'uploads/coach/') === 0) {
            $filename = basename($current_path);
            $new_path = 'uploads/coaches/' . $filename;
            
            if (move_file_to_correct_directory($current_path, $new_path)) {
                $db->query("UPDATE coaches SET coach_image = ? WHERE id = ?", [$new_path, $coach['id']]);
                $fixes_applied[] = "Coach photo: $coach_name - moved from $current_path to $new_path";
            } else {
                $errors[] = "Failed to move coach photo for $coach_name from $current_path to $new_path";
            }
        }
    }
}

// Display results
echo "<h3>Migration Results</h3>\n";

if (!empty($fixes_applied)) {
    echo "<h4 style='color: green;'>Successfully Applied Fixes (" . count($fixes_applied) . "):</h4>\n";
    echo "<ul>\n";
    foreach ($fixes_applied as $fix) {
        echo "<li>$fix</li>\n";
    }
    echo "</ul>\n";
}

if (!empty($errors)) {
    echo "<h4 style='color: red;'>Errors (" . count($errors) . "):</h4>\n";
    echo "<ul>\n";
    foreach ($errors as $error) {
        echo "<li>$error</li>\n";
    }
    echo "</ul>\n";
}

if (empty($fixes_applied) && empty($errors)) {
    echo "<p style='color: blue;'>No image path issues found. All paths are already consistent!</p>\n";
}

echo "<p><a href='upload_photos.php'>← Back to Upload Photos</a></p>\n";
?>
