<?php
/**
 * Direct Email Test
 * Test email sending directly without queue
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/mailer.php';

echo "<h2>Direct Email Test</h2>";

// Test email details
$test_email = 'governorwavinyacup@gmail.com'; // Using the same email as sender for testing
$test_subject = 'Test Email from Wavinya Cup System - ' . date('Y-m-d H:i:s');
$test_body = '
<h3>Email System Test</h3>
<p>This is a test email to verify the email system is working correctly.</p>
<p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>
<p><strong>From:</strong> Wavinya Cup Registration System</p>
<hr>
<p>If you receive this email, the email system is working properly.</p>
';

echo "<p><strong>Testing email to:</strong> " . $test_email . "</p>";
echo "<p><strong>Subject:</strong> " . $test_subject . "</p>";

// Check email configuration
echo "<h3>Email Configuration Check:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> " . ($_ENV['SMTP_HOST'] ?? 'Not set') . "</li>";
echo "<li><strong>SMTP Port:</strong> " . ($_ENV['SMTP_PORT'] ?? 'Not set') . "</li>";
echo "<li><strong>SMTP Username:</strong> " . ($_ENV['SMTP_USERNAME'] ?? 'Not set') . "</li>";
echo "<li><strong>SMTP Password:</strong> " . (isset($_ENV['SMTP_PASSWORD']) && !empty($_ENV['SMTP_PASSWORD']) ? 'Set (length: ' . strlen($_ENV['SMTP_PASSWORD']) . ')' : 'Not set') . "</li>";
echo "<li><strong>SMTP Encryption:</strong> " . ($_ENV['SMTP_ENCRYPTION'] ?? 'Not set') . "</li>";
echo "<li><strong>App Email:</strong> " . ($_ENV['APP_EMAIL'] ?? 'Not set') . "</li>";
echo "</ul>";

// Test PHPMailer availability
echo "<h3>PHPMailer Check:</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ Composer autoload found</p>";
    
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p style='color: green;'>✓ PHPMailer class available</p>";
    } else {
        echo "<p style='color: red;'>✗ PHPMailer class not found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Composer autoload not found</p>";
}

echo "<hr>";
echo "<h3>Sending Test Email...</h3>";

try {
    $result = send_email($test_email, $test_subject, $test_body);
    
    if ($result) {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ EMAIL SENT SUCCESSFULLY!</strong></p>";
        echo "<p>Check the inbox for: " . $test_email . "</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'><strong>✗ EMAIL FAILED TO SEND</strong></p>";
        echo "<p>Check the error logs for more details.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception occurred:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>If email sent successfully, check the email queue processing</li>";
echo "<li>If email failed, check SMTP credentials and server settings</li>";
echo "<li>Run <a href='debug_email_system.php'>Email System Debug</a> for more details</li>";
echo "<li>Run <a href='test_email_queue_manually.php'>Manual Queue Processing</a> to test queued emails</li>";
echo "</ul>";
?>
