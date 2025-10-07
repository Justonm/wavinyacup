<?php
// Detailed SMTP debugging tool
require_once __DIR__ . '/config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("PHPMailer not found. Please install via Composer.");
}

echo "<h2>SMTP Debug Tool</h2>\n";

// Display current configuration
echo "<h3>Current Configuration:</h3>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
echo "<tr><td>SMTP_HOST</td><td>" . ($_ENV['SMTP_HOST'] ?? 'NOT SET') . "</td></tr>\n";
echo "<tr><td>SMTP_PORT</td><td>" . ($_ENV['SMTP_PORT'] ?? 'NOT SET') . "</td></tr>\n";
echo "<tr><td>SMTP_USERNAME</td><td>" . ($_ENV['SMTP_USERNAME'] ?? 'NOT SET') . "</td></tr>\n";
echo "<tr><td>SMTP_PASSWORD</td><td>" . (empty($_ENV['SMTP_PASSWORD']) ? 'NOT SET' : 'SET (length: ' . strlen($_ENV['SMTP_PASSWORD']) . ')') . "</td></tr>\n";
echo "<tr><td>APP_EMAIL</td><td>" . ($_ENV['APP_EMAIL'] ?? 'NOT SET') . "</td></tr>\n";
echo "</table>\n";

if (isset($_GET['test'])) {
    echo "<h3>Detailed SMTP Test:</h3>\n";
    
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px 0; font-family: monospace; font-size: 12px;'>";
            echo htmlspecialchars($str);
            echo "</div>\n";
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 465;
        
        // Relaxed SSL settings for shared hosting
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $test_email = $_ENV['ADMIN_EMAILS'] ?? $_ENV['SMTP_USERNAME'];
        $mail->setFrom($_ENV['APP_EMAIL'], 'Wavinyacup System');
        $mail->addAddress($test_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SMTP Debug Test - ' . date('Y-m-d H:i:s');
        $mail->Body = '<h2>SMTP Test Successful</h2><p>This email confirms SMTP is working correctly.</p>';
        
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Attempting to send email...</strong><br>";
        echo "From: " . $_ENV['APP_EMAIL'] . "<br>";
        echo "To: " . $test_email . "<br>";
        echo "</div>\n";
        
        $mail->send();
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Email sent successfully!";
        echo "</div>\n";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ ERROR:</strong> " . $mail->ErrorInfo;
        echo "<br><strong>Exception:</strong> " . $e->getMessage();
        echo "</div>\n";
        
        // Common error solutions
        echo "<h3>Common Solutions:</h3>\n";
        echo "<ul>\n";
        
        if (strpos($e->getMessage(), 'Authentication failed') !== false) {
            echo "<li><strong>Authentication Failed:</strong> Check your Gmail app password</li>\n";
            echo "<li>Go to Google Account → Security → 2-Step Verification → App passwords</li>\n";
            echo "<li>Generate a new app password and update .env file</li>\n";
        }
        
        if (strpos($e->getMessage(), 'Connection refused') !== false) {
            echo "<li><strong>Connection Refused:</strong> Server may block port 465</li>\n";
            echo "<li>Try port 587 with STARTTLS instead</li>\n";
            echo "<li>Contact hosting provider about SMTP restrictions</li>\n";
        }
        
        if (strpos($e->getMessage(), 'SSL') !== false) {
            echo "<li><strong>SSL Issues:</strong> Try alternative configuration</li>\n";
            echo "<li>Some shared hosts have SSL certificate issues</li>\n";
        }
        
        echo "</ul>\n";
    }
}

// Alternative configuration test
if (isset($_GET['test_alt'])) {
    echo "<h3>Testing Alternative Configuration (Port 587 + STARTTLS):</h3>\n";
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px 0; font-family: monospace; font-size: 12px;'>";
            echo htmlspecialchars($str);
            echo "</div>\n";
        };
        
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $test_email = $_ENV['ADMIN_EMAILS'] ?? $_ENV['SMTP_USERNAME'];
        $mail->setFrom($_ENV['APP_EMAIL'], 'Wavinyacup System');
        $mail->addAddress($test_email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Alternative SMTP Test - ' . date('Y-m-d H:i:s');
        $mail->Body = '<h2>Alternative SMTP Test</h2><p>Testing port 587 with STARTTLS.</p>';
        
        $mail->send();
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Alternative configuration works!";
        echo "<br>Consider updating your mailer.php to use port 587 + STARTTLS";
        echo "</div>\n";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ Alternative config also failed:</strong> " . $e->getMessage();
        echo "</div>\n";
    }
}

echo "<hr>\n";
echo "<p><a href='?test=1' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Test Current Config</a> ";
echo "<a href='?test_alt=1' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Test Alternative Config</a></p>\n";

echo "<h3>Manual Gmail App Password Setup:</h3>\n";
echo "<ol>\n";
echo "<li>Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>\n";
echo "<li>Enable 2-Step Verification if not already enabled</li>\n";
echo "<li>Go to App passwords section</li>\n";
echo "<li>Generate new app password for 'Mail'</li>\n";
echo "<li>Update SMTP_PASSWORD in .env file with the new password</li>\n";
echo "</ol>\n";
?>
