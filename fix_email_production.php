<?php
// fix_email_production.php - Fix email issues in production
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

// Security check
if (!isset($_GET['fix']) || $_GET['fix'] !== 'emails2025') {
    die('Access denied');
}

echo "<h2>Email System Fix</h2>";

$db = db();

// Step 1: Reset failed emails to pending (give them another chance)
echo "<h3>1. Resetting Failed Emails</h3>";
$reset_result = $db->query("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE status = 'failed'");
$reset_count = $reset_result->rowCount();
echo "✅ Reset {$reset_count} failed emails to pending status<br>";

// Step 2: Test SMTP with corrected settings
echo "<h3>2. Testing SMTP Configuration</h3>";

// Override SMTP settings for testing
$_ENV['SMTP_HOST'] = 'smtp.gmail.com';
$_ENV['SMTP_PORT'] = '587';  // Try port 587 with TLS instead of 465 with SSL
$_ENV['SMTP_ENCRYPTION'] = 'tls';

echo "Testing with corrected settings:<br>";
echo "SMTP_HOST: " . $_ENV['SMTP_HOST'] . "<br>";
echo "SMTP_PORT: " . $_ENV['SMTP_PORT'] . "<br>";
echo "SMTP_ENCRYPTION: " . $_ENV['SMTP_ENCRYPTION'] . "<br>";

// Test email send
$test_email = $_ENV['APP_EMAIL'] ?? 'governorwavinyacup@gmail.com';
$test_subject = "Production Email Test - " . date('Y-m-d H:i:s');
$test_body = "This is a test email to verify SMTP configuration is working in production.";

echo "<h3>3. Sending Test Email</h3>";
if (send_email($test_email, $test_subject, $test_body)) {
    echo "✅ Test email sent successfully!<br>";
    echo "Check your inbox at {$test_email}<br>";
} else {
    echo "❌ Test email failed. Check error logs.<br>";
}

// Step 3: Process a few pending emails manually
echo "<h3>4. Processing Pending Emails</h3>";
$pending_emails = $db->fetchAll("SELECT * FROM email_queue WHERE status = 'pending' LIMIT 3");

if (empty($pending_emails)) {
    echo "No pending emails to process.<br>";
} else {
    foreach ($pending_emails as $email) {
        echo "Processing email to {$email['recipient']}... ";
        if (send_email($email['recipient'], $email['subject'], $email['body'])) {
            $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$email['id']]);
            echo "✅ Sent<br>";
        } else {
            $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
            echo "❌ Failed<br>";
        }
    }
}

// Step 4: Show current queue status
echo "<h3>5. Current Queue Status</h3>";
$stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM email_queue
");

echo "Total: {$stats['total']}, Pending: {$stats['pending']}, Sent: {$stats['sent']}, Failed: {$stats['failed']}<br>";

echo "<h3>6. Next Steps</h3>";
echo "1. If test email worked, update your .env file with port 587 and TLS<br>";
echo "2. Monitor the queue at <a href='admin/process_emails.php'>admin/process_emails.php</a><br>";
echo "3. Check cron job is running: <code>* * * * * /usr/bin/php " . __DIR__ . "/cron/send_queued_emails.php</code><br>";
?>
