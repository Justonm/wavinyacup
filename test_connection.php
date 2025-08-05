<?php
echo '<pre>__FILE__: ' . __FILE__ . '</pre>';
echo '<pre>CONFIG PATH: ' . realpath(__DIR__ . '/config/database.php') . '</pre>';
require_once 'config/database.php';

echo "<h1>Machakos County Team Registration System - Connection Test</h1>";

try {
    $db = db();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    // Test query
    $result = $db->fetchRow("SELECT COUNT(*) as count FROM users");
    echo "<p>Total users in database: " . $result['count'] . "</p>";

    // Test counties
    $counties = $db->fetchAll("SELECT * FROM counties");
    echo "<p>Counties in database: " . count($counties) . "</p>";

    // Test sub-counties
    $sub_counties = $db->fetchAll("SELECT * FROM sub_counties");
    echo "<p>Sub-counties in database: " . count($sub_counties) . "</p>";

    // Test wards
    $wards = $db->fetchAll("SELECT * FROM wards");
    echo "<p>Wards in database: " . count($wards) . "</p>";

    echo "<p style='color: green;'>✅ All database tables are working correctly!</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
?>