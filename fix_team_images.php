<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = db();

echo "<h3>Fixing Team Image References</h3>";

// Get all teams
$teams = $db->fetchAll("SELECT id, name FROM teams ORDER BY id");

// Get all team image files
$uploads_dir = __DIR__ . '/uploads/teams';
$files = scandir($uploads_dir);
$image_files = array_filter($files, function($file) {
    return $file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
});

echo "<p>Found " . count($image_files) . " image files in teams directory:</p>";
foreach ($image_files as $file) {
    echo "<li>" . htmlspecialchars($file) . "</li>";
}

echo "<h4>Manual Assignment (you'll need to match these manually):</h4>";
echo "<p>Run these SQL commands in phpMyAdmin to assign images to teams:</p>";

$team_index = 0;
foreach ($teams as $team) {
    if (isset($image_files[array_keys($image_files)[$team_index % count($image_files)]])) {
        $file = $image_files[array_keys($image_files)[$team_index % count($image_files)]];
        
        if (strpos($file, 'logo') !== false) {
            echo "<code>UPDATE teams SET logo_path = 'teams/" . $file . "' WHERE id = " . $team['id'] . "; -- " . htmlspecialchars($team['name']) . "</code><br>";
        } else {
            echo "<code>UPDATE teams SET team_photo = 'teams/" . $file . "' WHERE id = " . $team['id'] . "; -- " . htmlspecialchars($team['name']) . "</code><br>";
        }
    }
    $team_index++;
}

echo "<p><strong>After running the SQL commands above, refresh the teams page to see the images.</strong></p>";
?>
