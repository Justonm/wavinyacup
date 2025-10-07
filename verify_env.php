<?php
// verify_env.php - Check if .env file has been updated
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['check']) || $_GET['check'] !== 'env2025') {
    die('Access denied');
}

echo "<h2>Environment Variable Check</h2>";

// Load config
require_once __DIR__ . '/config/config.php';

echo "<h3>Current Environment Variables</h3>";
echo "SMTP_USERNAME: " . ($_ENV['SMTP_USERNAME'] ?? 'NOT SET') . "<br>";
echo "SMTP_PASSWORD: " . ($_ENV['SMTP_PASSWORD'] ?? 'NOT SET') . "<br>";
echo "SMTP_PORT: " . ($_ENV['SMTP_PORT'] ?? 'NOT SET') . "<br>";
echo "SMTP_ENCRYPTION: " . ($_ENV['SMTP_ENCRYPTION'] ?? 'NOT SET') . "<br>";

echo "<h3>Expected Values</h3>";
echo "SMTP_PASSWORD should be: <strong>icxk dzdc eglt btye</strong><br>";
echo "SMTP_PORT should be: <strong>587</strong><br>";
echo "SMTP_ENCRYPTION should be: <strong>tls</strong><br>";

echo "<h3>Status</h3>";
if (($_ENV['SMTP_PASSWORD'] ?? '') === 'icxk dzdc eglt btye') {
    echo "✅ Password updated correctly<br>";
} else {
    echo "❌ Password NOT updated. Current: " . ($_ENV['SMTP_PASSWORD'] ?? 'NOT SET') . "<br>";
    echo "<strong>Action needed:</strong> Update your .env file in cPanel<br>";
}

if (($_ENV['SMTP_PORT'] ?? '') === '587') {
    echo "✅ Port updated correctly<br>";
} else {
    echo "❌ Port NOT updated. Current: " . ($_ENV['SMTP_PORT'] ?? 'NOT SET') . "<br>";
}

if (($_ENV['SMTP_ENCRYPTION'] ?? '') === 'tls') {
    echo "✅ Encryption updated correctly<br>";
} else {
    echo "❌ Encryption NOT updated. Current: " . ($_ENV['SMTP_ENCRYPTION'] ?? 'NOT SET') . "<br>";
}

echo "<h3>Next Steps</h3>";
echo "1. If values are incorrect, update your .env file in cPanel<br>";
echo "2. Make sure to save the .env file after editing<br>";
echo "3. Test again after updating<br>";
?>
