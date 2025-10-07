<?php
// add_muthetheni_ward.php - Add Muthetheni ward to the database
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = db();

try {
    // First, let's check which sub-county Muthetheni should belong to
    // Based on the existing data, let's see what sub-counties we have
    $sub_counties = $db->fetchAll("SELECT * FROM sub_counties ORDER BY name");
    
    echo "<h2>Available Sub-Counties:</h2>";
    foreach ($sub_counties as $sc) {
        echo "ID: {$sc['id']}, Name: {$sc['name']}, Code: {$sc['code']}<br>";
    }
    
    // Let's assume Muthetheni belongs to Machakos Town sub-county (ID: 1) for now
    // You can change this based on the correct administrative division
    $sub_county_id = 1; // Machakos Town
    
    // Check if Muthetheni already exists
    $existing = $db->fetchRow("SELECT * FROM wards WHERE name = 'Muthetheni'");
    
    if ($existing) {
        echo "<p style='color: green;'>Muthetheni ward already exists with ID: {$existing['id']}</p>";
    } else {
        // Add Muthetheni ward
        $db->query(
            "INSERT INTO wards (sub_county_id, name, code) VALUES (?, ?, ?)",
            [$sub_county_id, 'Muthetheni', 'MUTH_' . $sub_county_id]
        );
        
        $ward_id = $db->lastInsertId();
        echo "<p style='color: green;'>Successfully added Muthetheni ward with ID: {$ward_id}</p>";
    }
    
    // Display all wards to verify
    echo "<h2>All Wards (including new one):</h2>";
    $all_wards = $db->fetchAll("
        SELECT w.*, sc.name as sub_county_name 
        FROM wards w 
        JOIN sub_counties sc ON w.sub_county_id = sc.id 
        ORDER BY sc.name, w.name
    ");
    
    foreach ($all_wards as $ward) {
        $highlight = ($ward['name'] === 'Muthetheni') ? 'style="background-color: yellow;"' : '';
        echo "<div {$highlight}>Ward: {$ward['name']} (Code: {$ward['code']}) - Sub-County: {$ward['sub_county_name']}</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
