<?php
// php_check.php - Check PHP version and configuration
if (!isset($_GET['access']) || $_GET['access'] !== 'phpcheck2025') {
    die('Access denied - use ?access=phpcheck2025');
}

echo "<h1>PHP Configuration Check</h1>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check critical extensions
$extensions = ['pdo', 'pdo_mysql', 'openssl', 'curl', 'mbstring'];
echo "<h3>Required Extensions:</h3>";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>{$ext}: " . ($loaded ? '✅ Loaded' : '❌ Missing') . "</p>";
}

// Check file permissions
$files_to_check = [
    __DIR__ . '/../.env',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../config/config.php'
];

echo "<h3>File Access:</h3>";
foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    echo "<p>" . basename($file) . ": " . ($exists ? ($readable ? '✅ Accessible' : '⚠️ Not readable') : '❌ Missing') . "</p>";
}

// Check error reporting
echo "<h3>Error Settings:</h3>";
echo "<p>Error Reporting: " . error_reporting() . "</p>";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>";
echo "<p>Log Errors: " . ini_get('log_errors') . "</p>";

// Memory and execution limits
echo "<h3>Resource Limits:</h3>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";
echo "<p>Upload Max Filesize: " . ini_get('upload_max_filesize') . "</p>";

// Test basic PHP features
echo "<h3>PHP Feature Tests:</h3>";

// Test array functions
try {
    $test_array = ['a', 'b', 'c'];
    $result = array_map('strtoupper', $test_array);
    echo "<p>Array functions: ✅ Working</p>";
} catch (Exception $e) {
    echo "<p>Array functions: ❌ Error - " . $e->getMessage() . "</p>";
}

// Test string functions
try {
    $test = str_shuffle('abcdef');
    echo "<p>String functions: ✅ Working</p>";
} catch (Exception $e) {
    echo "<p>String functions: ❌ Error - " . $e->getMessage() . "</p>";
}

// Test class instantiation
try {
    $pdo_available = class_exists('PDO');
    echo "<p>PDO Class: " . ($pdo_available ? '✅ Available' : '❌ Missing') . "</p>";
} catch (Exception $e) {
    echo "<p>PDO Class: ❌ Error - " . $e->getMessage() . "</p>";
}

// Show PHP info link
echo "<hr>";
echo "<p><a href='?access=phpcheck2025&info=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Show Full PHP Info</a></p>";

if (isset($_GET['info']) && $_GET['info'] == '1') {
    echo "<hr>";
    phpinfo();
}
?>
