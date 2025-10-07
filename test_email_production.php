<?php
// test_email_production.php - Test email configuration in production
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/mailer.php';

// Only allow access from admin or specific IP
if (!isset($_GET['test']) || $_GET['test'] !== 'email123') {
    die('Access denied');
}

echo "<h2>Email Configuration Test</h2>";

// Test 1: Check environment variables
echo "<h3>1. Environment Variables</h3>";
echo "SMTP_HOST: " . ($_ENV['SMTP_HOST'] ?? 'NOT SET') . "<br>";
echo "SMTP_PORT: " . ($_ENV['SMTP_PORT'] ?? 'NOT SET') . "<br>";
echo "SMTP_USERNAME: " . ($_ENV['SMTP_USERNAME'] ?? 'NOT SET') . "<br>";
echo "SMTP_PASSWORD: " . (isset($_ENV['SMTP_PASSWORD']) ? '***SET***' : 'NOT SET') . "<br>";
echo "SMTP_ENCRYPTION: " . ($_ENV['SMTP_ENCRYPTION'] ?? 'NOT SET') . "<br>";
echo "APP_EMAIL: " . ($_ENV['APP_EMAIL'] ?? 'NOT SET') . "<br>";

// Test 2: Check PHPMailer availability
echo "<h3>2. PHPMailer Status</h3>";
if (defined('PHPMAILER_AVAILABLE') && PHPMAILER_AVAILABLE) {
    echo "✅ PHPMailer is available<br>";
} else {
    echo "❌ PHPMailer is NOT available<br>";
}

// Test 3: Database connection for email queue
echo "<h3>3. Database & Email Queue</h3>";
try {
    $db = db();
    $pending_count = $db->fetchCell("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    echo "✅ Database connected<br>";
    echo "Pending emails in queue: {$pending_count}<br>";
    
    $recent = $db->fetchRow("SELECT * FROM email_queue ORDER BY created_at DESC LIMIT 1");
    if ($recent) {
        echo "Latest email: " . htmlspecialchars($recent['subject']) . " to " . htmlspecialchars($recent['recipient']) . " (" . $recent['status'] . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Send test email
echo "<h3>4. Test Email Send</h3>";
$test_email = $_ENV['APP_EMAIL'] ?? 'governorwavinyacup@gmail.com';
$test_subject = "Test Email - " . date('Y-m-d H:i:s');
$test_body = "This is a test email sent from production server at " . date('Y-m-d H:i:s');

if (send_email($test_email, $test_subject, $test_body)) {
    echo "✅ Test email sent successfully to {$test_email}<br>";
} else {
    echo "❌ Failed to send test email<br>";
}

// Test 5: Queue test email
echo "<h3>5. Test Email Queue</h3>";
if (queue_email($test_email, "Queued Test - " . date('H:i:s'), "This is a queued test email.")) {
    echo "✅ Test email queued successfully<br>";
} else {
    echo "❌ Failed to queue test email<br>";
}

echo "<br><strong>Next Steps:</strong><br>";
echo "1. If environment variables are missing, check your .env file<br>";
echo "2. If PHPMailer is not available, run: composer install<br>";
echo "3. If emails are queued but not sent, set up the cron job<br>";
echo "4. Access the email queue manager at: <a href='admin/process_emails.php'>admin/process_emails.php</a><br>";
?>
