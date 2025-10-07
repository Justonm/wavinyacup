<?php
// /test_db_connection.php
// A simple script to verify the database connection.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";
echo "<pre>";

// Load configuration which includes the .env file loader
require_once __DIR__ . '/config/config.php';

// The db() function is in db.php, which is included by config.php
// Let's explicitly include it to be safe.
require_once __DIR__ . '/includes/db.php';

try {
    // Attempt to get the database connection
    $pdo = db();

    if ($pdo) {
        echo "✅ SUCCESS: Database connection established successfully.\n";
        
        // Optional: Check server version
        $server_info = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        echo "MySQL Server Version: " . htmlspecialchars($server_info) . "\n";
        
        // Optional: Check connection status
        $status = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        echo "Connection Status: " . htmlspecialchars($status) . "\n";

    } else {
        echo "❌ FAILED: The db() function returned a null or false value. Check includes/db.php.\n";
    }
} catch (PDOException $e) {
    echo "❌ PDO EXCEPTION: Could not connect to the database.\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Please check your .env file for the correct database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS).\n";
} catch (Exception $e) {
    echo "❌ GENERAL EXCEPTION: An unexpected error occurred.\n";
    echo "Error Message: " . $e->getMessage() . "\n";
}

echo "</pre>";

?>
