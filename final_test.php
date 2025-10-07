<?php
// final_test.php - Comprehensive email test with multiple approaches
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['test']) || $_GET['test'] !== 'final2025') {
    die('Access denied');
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Final Email Test - Multiple Approaches</h2>";

// Test 1: Environment check
echo "<h3>1. Environment Variables</h3>";
echo "SMTP_USERNAME: " . $_ENV['SMTP_USERNAME'] . "<br>";
echo "SMTP_PASSWORD: " . (strlen($_ENV['SMTP_PASSWORD']) > 0 ? '[' . strlen($_ENV['SMTP_PASSWORD']) . ' characters]' : 'NOT SET') . "<br>";
echo "SMTP_PORT: " . $_ENV['SMTP_PORT'] . "<br>";
echo "SMTP_ENCRYPTION: " . $_ENV['SMTP_ENCRYPTION'] . "<br>";

// Test 2: Try with relaxed SSL settings
echo "<h3>2. Testing with Relaxed SSL Settings</h3>";
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    
    // Relaxed SSL settings for shared hosting
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
        )
    );
    
    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
    $mail->addAddress($_ENV['SMTP_USERNAME'], 'Test Recipient');
    $mail->Subject = 'Test Email - Relaxed SSL';
    $mail->Body = 'This is a test email with relaxed SSL settings.';
    
    $result = $mail->send();
    echo "✅ SUCCESS with relaxed SSL settings!<br>";
    echo "<strong>This configuration works - use this for your mailer!</strong><br>";
} catch (Exception $e) {
    echo "❌ Relaxed SSL test failed: " . $e->getMessage() . "<br>";
}

// Test 3: Try without STARTTLS
echo "<h3>3. Testing without STARTTLS</h3>";
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = false; // No encryption
    $mail->SMTPAutoTLS = false; // Disable auto TLS
    $mail->Port = 587;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    
    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
    $mail->addAddress($_ENV['SMTP_USERNAME'], 'Test Recipient');
    $mail->Subject = 'Test Email - No TLS';
    $mail->Body = 'This is a test email without TLS.';
    
    $result = $mail->send();
    echo "✅ SUCCESS without TLS!<br>";
} catch (Exception $e) {
    echo "❌ No TLS test failed: " . $e->getMessage() . "<br>";
}

// Test 4: Try port 465 with SSL
echo "<h3>4. Testing Port 465 with SSL</h3>";
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    
    // Relaxed SSL for shared hosting
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
    $mail->addAddress($_ENV['SMTP_USERNAME'], 'Test Recipient');
    $mail->Subject = 'Test Email - Port 465';
    $mail->Body = 'This is a test email using port 465.';
    
    $result = $mail->send();
    echo "✅ SUCCESS with port 465!<br>";
} catch (Exception $e) {
    echo "❌ Port 465 test failed: " . $e->getMessage() . "<br>";
}

// Test 5: Check Gmail account status
echo "<h3>5. Gmail Account Recommendations</h3>";
echo "If all tests failed, check:<br>";
echo "1. ✓ 2FA is enabled on Gmail account<br>";
echo "2. ✓ App password is generated and correct<br>";
echo "3. ✓ 'Less secure app access' is disabled (use app passwords)<br>";
echo "4. ✓ Check Gmail security settings for blocked sign-ins<br>";
echo "5. ✓ Your server IP might be blocked by Gmail<br>";
echo "6. ✓ Contact hosting provider about SMTP restrictions<br>";

echo "<h3>6. Alternative Solutions</h3>";
echo "Consider switching to:<br>";
echo "• SendGrid (more reliable for shared hosting)<br>";
echo "• Mailgun<br>";
echo "• Amazon SES<br>";
echo "• Your hosting provider's SMTP service<br>";
?> 