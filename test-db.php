<?php
require_once __DIR__ . '/vendor/autoload.php'; // If you're using Composer packages

// If you have a custom database connection file:
require_once __DIR__ . '/config/database.php'; // Update this if your db function lives elsewhere

try {
    $db = db()->getConnection(); // assuming db() returns an object with getConnection()
    $results = $db->query("SELECT * FROM test_table")->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($results);
    echo "</pre>";
} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage();
}
