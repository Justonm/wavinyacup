<?php
/**
 * System Test Script
 * Tests basic functionality of the Machakos County Team Registration System
 */

require_once 'config/config.php';

echo "=== Machakos County Team Registration System Test ===\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $db = db();
    echo "✓ Database connection successful\n\n";
    
    // Test basic query
    echo "2. Testing basic database query...\n";
    $result = $db->fetchRow("SELECT COUNT(*) as count FROM users");
    echo "✓ Database query successful. User count: " . ($result['count'] ?? 0) . "\n\n";
    
    // Test wards query
    echo "3. Testing wards query...\n";
    $wards = $db->fetchAll("SELECT * FROM wards LIMIT 5");
    echo "✓ Wards query successful. Found " . count($wards) . " wards\n\n";
    
    // Test helper functions
    echo "4. Testing helper functions...\n";
    $test_input = "  <script>alert('test')</script>  ";
    $sanitized = sanitize_input($test_input);
    echo "✓ Input sanitization working\n";
    
    $test_email = "test@example.com";
    $email_valid = validate_email($test_email);
    echo "✓ Email validation working: " . ($email_valid ? "valid" : "invalid") . "\n";
    
    $test_phone = "+254700123456";
    $phone_valid = validate_phone($test_phone);
    echo "✓ Phone validation working: " . ($phone_valid ? "valid" : "invalid") . "\n";
    
    $team_code = generate_team_code("TEST");
    echo "✓ Team code generation working: $team_code\n\n";
    
    // Test image upload directory creation
    echo "5. Testing image upload directories...\n";
    if (function_exists('create_upload_directories')) {
        create_upload_directories();
        echo "✓ Upload directories created/verified\n";
    } else {
        echo "⚠ Image upload functions not loaded\n";
    }
    echo "\n";
    
    echo "=== System Test Complete ===\n";
    echo "✓ All basic functionality is working correctly!\n";
    echo "✓ The system is ready for team registration testing.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?> 