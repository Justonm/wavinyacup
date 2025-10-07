<?php
// test_smtp_direct.php - Direct SMTP test with multiple configurations
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security check
if (!isset($_GET['smtp']) || $_GET['smtp'] !== 'direct2025') {
    die('Access denied');
}

echo "<h2>Direct SMTP Test</h2>";

// Load config
require_once __DIR__ . '/config/config.php';

// Check if PHPMailer is available
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("❌ PHPMailer not found. Run 'composer install' first.");
}

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

echo "<h3>Environment Variables</h3>";
echo "SMTP_USERNAME: " . ($_ENV['SMTP_USERNAME'] ?? 'NOT SET') . "<br>";
echo "SMTP_PASSWORD: " . (isset($_ENV['SMTP_PASSWORD']) ? '***SET***' : 'NOT SET') . "<br>";
echo "APP_EMAIL: " . ($_ENV['APP_EMAIL'] ?? 'NOT SET') . "<br>";

// Test configurations
$configs = [
    [
        'name' => 'Gmail TLS 587',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS
    ],
    [
        'name' => 'Gmail SSL 465', 
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'encryption' => PHPMailer::ENCRYPTION_SMTPS
    ]
];

foreach ($configs as $config) {
    echo "<h3>Testing: {$config['name']}</h3>";
    
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        $mail->Debugoutput = function($str, $level) {
            echo "Debug level $level; message: $str<br>";
        };
        
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->Timeout = 30;
        
        // SSL options for shared hosting
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Set up the email
        $mail->setFrom($_ENV['APP_EMAIL'], 'Wavinya Cup Test');
        $mail->addAddress($_ENV['APP_EMAIL']);
        $mail->Subject = 'SMTP Test - ' . $config['name'] . ' - ' . date('H:i:s');
        $mail->Body = 'This is a test email using ' . $config['name'] . ' at ' . date('Y-m-d H:i:s');
        
        // Attempt to send
        if ($mail->send()) {
            echo "<div style='color: green; font-weight: bold;'>✅ SUCCESS with {$config['name']}!</div>";
            echo "<p>Email sent successfully. Check your inbox.</p>";
            break; // Stop testing other configs if one works
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ FAILED with {$config['name']}</div>";
        echo "<p>Error: " . $mail->ErrorInfo . "</p>";
        echo "<p>Exception: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<h3>Recommendations</h3>";
echo "<p>1. If any configuration worked, update your mailer.php to use that configuration</p>";
echo "<p>2. If none worked, contact your hosting provider about SMTP restrictions</p>";
echo "<p>3. Consider using a transactional email service like SendGrid or Mailgun</p>";
?>
