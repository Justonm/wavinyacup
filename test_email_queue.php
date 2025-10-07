<?php
// Test script to manually process email queue and diagnose issues

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

echo "<h2>Email Queue Diagnostic Test</h2>\n";

$db = db();

// Check if email_queue table exists
try {
    $queue_count = $db->fetchColumn("SELECT COUNT(*) FROM email_queue");
    echo "<p>✓ Email queue table exists with {$queue_count} total emails</p>\n";
    
    // Check pending emails
    $pending_count = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    echo "<p>📧 Pending emails: {$pending_count}</p>\n";
    
    // Check failed emails
    $failed_count = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'");
    echo "<p>❌ Failed emails: {$failed_count}</p>\n";
    
    // Check sent emails
    $sent_count = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'");
    echo "<p>✅ Sent emails: {$sent_count}</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error accessing email_queue table: " . $e->getMessage() . "</p>\n";
    echo "<p>The email_queue table may not exist. Creating it now...</p>\n";
    
    // Create email_queue table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(255) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        body TEXT NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL
    )";
    
    try {
        $db->query($create_table_sql);
        echo "<p>✓ Email queue table created successfully</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Failed to create email_queue table: " . $e->getMessage() . "</p>\n";
    }
}

// Test SMTP configuration
echo "<h3>SMTP Configuration Test</h3>\n";
echo "<p>SMTP Host: " . ($_ENV['SMTP_HOST'] ?? 'Not set') . "</p>\n";
echo "<p>SMTP Port: " . ($_ENV['SMTP_PORT'] ?? 'Not set') . "</p>\n";
echo "<p>SMTP Username: " . ($_ENV['SMTP_USERNAME'] ?? 'Not set') . "</p>\n";
echo "<p>SMTP Password: " . (empty($_ENV['SMTP_PASSWORD']) ? 'Not set' : '****** (set)') . "</p>\n";

// Test sending a simple email
if (isset($_GET['test_send'])) {
    echo "<h3>Testing Email Send</h3>\n";
    $test_email = $_ENV['ADMIN_EMAILS'] ?? 'test@example.com';
    $subject = "Test Email from Wavinyacup System";
    $body = "<h2>Test Email</h2><p>This is a test email sent at " . date('Y-m-d H:i:s') . "</p>";
    
    if (send_email($test_email, $subject, $body)) {
        echo "<p>✅ Test email sent successfully to {$test_email}</p>\n";
    } else {
        echo "<p>❌ Failed to send test email to {$test_email}</p>\n";
    }
}

// Process pending emails manually
if (isset($_GET['process_queue'])) {
    echo "<h3>Processing Email Queue Manually</h3>\n";
    
    $emails = $db->fetchAll("
        SELECT * FROM email_queue 
        WHERE (status = 'pending' OR status = 'failed') 
        AND attempts < 5
        ORDER BY created_at ASC 
        LIMIT 5
    ");
    
    if (empty($emails)) {
        echo "<p>No pending emails to process</p>\n";
    } else {
        echo "<p>Processing " . count($emails) . " emails...</p>\n";
        
        foreach ($emails as $email) {
            echo "<p>Processing email ID {$email['id']} to {$email['recipient']}... ";
            
            if (send_email($email['recipient'], $email['subject'], $email['body'])) {
                $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                echo "✅ Success</p>\n";
            } else {
                $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                echo "❌ Failed</p>\n";
            }
        }
    }
}

echo "<hr>\n";
echo "<p><a href='?test_send=1'>Test Send Email</a> | <a href='?process_queue=1'>Process Queue Manually</a></p>\n";
echo "<p><strong>Note:</strong> After fixing issues, set up a cron job to run: <code>php " . __DIR__ . "/cron/send_queued_emails.php</code></p>\n";
?>
