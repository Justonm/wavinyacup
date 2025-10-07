<?php
// Quick test to verify the Gmail SMTP fix is working
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

echo "<h2>Gmail SMTP Test</h2>\n";

// Test current configuration
echo "<h3>Current Configuration:</h3>\n";
echo "<p>SMTP Host: " . ($_ENV['SMTP_HOST'] ?? 'Not set') . "</p>\n";
echo "<p>SMTP Port: " . ($_ENV['SMTP_PORT'] ?? 'Not set') . "</p>\n";
echo "<p>SMTP Username: " . ($_ENV['SMTP_USERNAME'] ?? 'Not set') . "</p>\n";
echo "<p>App Password: " . (empty($_ENV['SMTP_PASSWORD']) ? 'Not set' : 'Set (hidden)') . "</p>\n";

// Test email send
if (isset($_GET['send_test'])) {
    echo "<h3>Sending Test Email...</h3>\n";
    
    $test_email = $_ENV['ADMIN_EMAILS'] ?? 'governorwavinyacup@gmail.com';
    $subject = "Email System Test - " . date('Y-m-d H:i:s');
    $body = "
    <h2>Email System Test</h2>
    <p>This test email was sent at: " . date('Y-m-d H:i:s') . "</p>
    <p>If you receive this, your Gmail SMTP is working correctly!</p>
    <p>System: Wavinyacup Tournament Registration</p>
    ";
    
    echo "<p>Attempting to send to: {$test_email}</p>\n";
    
    if (send_email($test_email, $subject, $body)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Test email sent successfully.";
        echo "<br>Check your inbox at {$test_email}";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ FAILED!</strong> Could not send test email.";
        echo "<br>Check the error logs for details.";
        echo "</div>\n";
    }
}

// Check email queue
$db = db();
try {
    $pending = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    $failed = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'failed' AND attempts < 5");
    
    echo "<h3>Email Queue Status:</h3>\n";
    echo "<p>Pending emails: <strong>{$pending}</strong></p>\n";
    echo "<p>Failed (retryable): <strong>{$failed}</strong></p>\n";
    
    if ($pending > 0 || $failed > 0) {
        echo "<p><a href='test_email_queue.php?process_queue=1' style='background: #007cba; color: white; padding: 8px 12px; text-decoration: none; border-radius: 3px;'>Process Queue Now</a></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>Could not check email queue: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='?send_test=1' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Send Test Email</a></p>\n";

echo "<h3>Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Click 'Send Test Email' above to verify Gmail SMTP works</li>\n";
echo "<li>If successful, set up the cron job using <a href='setup_email_cron.php'>setup_email_cron.php</a></li>\n";
echo "<li>Test coach approval to ensure auto emails work</li>\n";
echo "</ol>\n";
?>
