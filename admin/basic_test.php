<?php
// basic_test.php - Most basic test possible
echo "PHP is working!<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";

if (isset($_GET['access']) && $_GET['access'] === 'basic2025') {
    echo "Access granted!<br>";
    
    // Test if we can include config
    if (file_exists(__DIR__ . '/../config/config.php')) {
        echo "Config file exists<br>";
        try {
            require_once __DIR__ . '/../config/config.php';
            echo "Config loaded successfully<br>";
        } catch (Exception $e) {
            echo "Config error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Config file not found<br>";
    }
    
} else {
    echo "Use ?access=basic2025 to continue<br>";
}
?>
