<?php
// Test script for coach registration functionality
require_once 'config/config.php';

echo "<h2>Coach Registration Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $db = db();
    echo "✅ Database connection successful<br>";
    
    // Test if required tables exist
    $tables = ['users', 'coaches', 'coach_registrations', 'wards', 'sub_counties'];
    foreach ($tables as $table) {
        $result = $db->fetchRow("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 2: Check wards data
echo "<h3>2. Wards Data Test</h3>";
try {
    $wards = $db->fetchAll("
        SELECT w.*, sc.name as sub_county_name 
        FROM wards w 
        JOIN sub_counties sc ON w.sub_county_id = sc.id 
        ORDER BY sc.name, w.name
        LIMIT 5
    ");
    
    if (count($wards) > 0) {
        echo "✅ Found " . count($wards) . " wards (showing first 5):<br>";
        foreach ($wards as $ward) {
            echo "- {$ward['name']} ({$ward['sub_county_name']})<br>";
        }
    } else {
        echo "❌ No wards found in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Error fetching wards: " . $e->getMessage() . "<br>";
}

// Test 3: Check helper functions
echo "<h3>3. Helper Functions Test</h3>";
if (function_exists('sanitize_input')) {
    echo "✅ sanitize_input function exists<br>";
} else {
    echo "❌ sanitize_input function missing<br>";
}

if (function_exists('validate_email')) {
    echo "✅ validate_email function exists<br>";
} else {
    echo "❌ validate_email function missing<br>";
}

if (function_exists('validate_phone')) {
    echo "✅ validate_phone function exists<br>";
} else {
    echo "❌ validate_phone function missing<br>";
}

// Test 4: Session and CSRF
echo "<h3>4. Session Test</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "❌ Session not active<br>";
}

// Test 5: File permissions
echo "<h3>5. File Permissions Test</h3>";
$upload_dir = ROOT_PATH . '/uploads/coaches/';
if (is_dir($upload_dir)) {
    if (is_writable($upload_dir)) {
        echo "✅ Upload directory is writable<br>";
    } else {
        echo "❌ Upload directory not writable<br>";
    }
} else {
    echo "❌ Upload directory doesn't exist<br>";
}

echo "<hr>";
echo "<p><a href='coaches/self_register.php'>Test Coach Registration Form</a></p>";
?>
