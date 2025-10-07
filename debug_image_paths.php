<?php
require_once 'config/config.php';
require_once 'includes/db.php';

$db = db();

echo "<h2>Debug Image Paths</h2>\n";

// Check players with images
$players = $db->fetchAll("
    SELECT id, first_name, last_name, player_image, id_photo_front, id_photo_back 
    FROM players 
    WHERE player_image IS NOT NULL OR id_photo_front IS NOT NULL OR id_photo_back IS NOT NULL 
    LIMIT 10
");

echo "<h3>Players with Images:</h3>\n";
foreach ($players as $player) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>\n";
    echo "<strong>{$player['first_name']} {$player['last_name']}</strong><br>\n";
    
    if ($player['player_image']) {
        echo "Player Image: {$player['player_image']}<br>\n";
        $file_path = ROOT_PATH . '/' . $player['player_image'];
        echo "File exists: " . (file_exists($file_path) ? 'YES' : 'NO') . "<br>\n";
        if (file_exists($file_path)) {
            echo "<img src='{$player['player_image']}' style='max-width: 100px; max-height: 100px;'><br>\n";
        }
    }
    
    if ($player['id_photo_front']) {
        echo "ID Front: {$player['id_photo_front']}<br>\n";
        $file_path = ROOT_PATH . '/' . $player['id_photo_front'];
        echo "File exists: " . (file_exists($file_path) ? 'YES' : 'NO') . "<br>\n";
        if (file_exists($file_path)) {
            echo "<img src='{$player['id_photo_front']}' style='max-width: 100px; max-height: 100px;'><br>\n";
        }
    }
    
    if ($player['id_photo_back']) {
        echo "ID Back: {$player['id_photo_back']}<br>\n";
        $file_path = ROOT_PATH . '/' . $player['id_photo_back'];
        echo "File exists: " . (file_exists($file_path) ? 'YES' : 'NO') . "<br>\n";
        if (file_exists($file_path)) {
            echo "<img src='{$player['id_photo_back']}' style='max-width: 100px; max-height: 100px;'><br>\n";
        }
    }
    
    echo "</div>\n";
}

echo "<h3>Upload Directory Structure:</h3>\n";
$upload_dirs = ['uploads/player', 'uploads/players', 'uploads/id'];
foreach ($upload_dirs as $dir) {
    $full_path = ROOT_PATH . '/' . $dir;
    echo "<strong>$dir:</strong> ";
    if (is_dir($full_path)) {
        $files = scandir($full_path);
        $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
        echo count($files) . " files<br>\n";
        foreach ($files as $file) {
            echo "  - $file<br>\n";
        }
    } else {
        echo "Directory does not exist<br>\n";
    }
}
?>
