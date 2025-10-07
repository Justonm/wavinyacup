<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = db();

echo "<h3>Team Photo Debug Information</h3>";

// Get all teams and their photo data
$teams = $db->fetchAll("SELECT id, name, team_photo, logo_path FROM teams ORDER BY id");

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>ID</th><th>Team Name</th><th>team_photo</th><th>logo_path</th><th>Photo File Exists?</th><th>Logo File Exists?</th></tr>";

foreach ($teams as $team) {
    echo "<tr>";
    echo "<td>" . $team['id'] . "</td>";
    echo "<td>" . htmlspecialchars($team['name']) . "</td>";
    echo "<td>" . htmlspecialchars($team['team_photo'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($team['logo_path'] ?? 'NULL') . "</td>";
    
    // Check if photo file exists
    $photo_exists = 'N/A';
    if (!empty($team['team_photo'])) {
        $photo_path = __DIR__ . '/uploads/' . $team['team_photo'];
        $photo_exists = file_exists($photo_path) ? 'YES' : 'NO';
    }
    echo "<td>" . $photo_exists . "</td>";
    
    // Check if logo file exists
    $logo_exists = 'N/A';
    if (!empty($team['logo_path'])) {
        $logo_path = __DIR__ . '/uploads/' . $team['logo_path'];
        $logo_exists = file_exists($logo_path) ? 'YES' : 'NO';
    }
    echo "<td>" . $logo_exists . "</td>";
    
    echo "</tr>";
}

echo "</table>";

// Check uploads directory structure
echo "<h3>Uploads Directory Structure</h3>";
$uploads_dir = __DIR__ . '/uploads';
if (is_dir($uploads_dir)) {
    echo "<p>Uploads directory exists at: " . $uploads_dir . "</p>";
    
    $subdirs = ['teams', 'coaches', 'players'];
    foreach ($subdirs as $subdir) {
        $full_path = $uploads_dir . '/' . $subdir;
        if (is_dir($full_path)) {
            $files = scandir($full_path);
            $files = array_filter($files, function($file) { return $file !== '.' && $file !== '..'; });
            echo "<p><strong>{$subdir}/</strong> directory exists with " . count($files) . " files:</p>";
            if (count($files) > 0) {
                echo "<ul>";
                foreach ($files as $file) {
                    echo "<li>" . htmlspecialchars($file) . "</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p><strong>{$subdir}/</strong> directory does NOT exist</p>";
        }
    }
} else {
    echo "<p>Uploads directory does NOT exist at: " . $uploads_dir . "</p>";
}

echo "<h3>APP_URL Configuration</h3>";
echo "<p>APP_URL: " . APP_URL . "</p>";
?>
