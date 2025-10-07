<?php
/**
 * API endpoint to fetch sub counties and wards data
 * Returns JSON data for populating dropdowns
 */
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

try {
    $db = db();
    
    // Get all sub counties
    $sub_counties = $db->fetchAll("
        SELECT id, name, code 
        FROM sub_counties 
        ORDER BY name ASC
    ");
    
    // Get all wards with sub county information
    $wards = $db->fetchAll("
        SELECT w.id, w.name, w.code, w.sub_county_id, sc.name as sub_county_name
        FROM wards w 
        JOIN sub_counties sc ON w.sub_county_id = sc.id 
        ORDER BY sc.name ASC, w.name ASC
    ");
    
    // Return structured data
    echo json_encode([
        'success' => true,
        'sub_counties' => $sub_counties,
        'wards' => $wards
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch location data: ' . $e->getMessage()
    ]);
}
?>
