<?php
/**
 * Debug Email System
 * Check email queue status and test email functionality
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

echo "<h2>Email System Debug Report</h2>";

$db = db();

// 1. Check if email_queue table exists
try {
    $tables = $db->fetchAll("SHOW TABLES LIKE 'email_queue'");
    if (empty($tables)) {
        echo "<p style='color: red;'><strong>ERROR:</strong> email_queue table does not exist!</p>";
        echo "<p>Creating email_queue table...</p>";
        
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
        
        $db->query($create_table_sql);
        echo "<p style='color: green;'>email_queue table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✓ email_queue table exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// 2. Check email queue contents
try {
    $queue_count = $db->fetchRow("SELECT COUNT(*) as count FROM email_queue");
    echo "<p><strong>Total emails in queue:</strong> " . $queue_count['count'] . "</p>";
    
    $pending_count = $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'");
    echo "<p><strong>Pending emails:</strong> " . $pending_count['count'] . "</p>";
    
    $sent_count = $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'sent'");
    echo "<p><strong>Sent emails:</strong> " . $sent_count['count'] . "</p>";
    
    $failed_count = $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'");
    echo "<p><strong>Failed emails:</strong> " . $failed_count['count'] . "</p>";
    
    // Show recent emails
    $recent_emails = $db->fetchAll("SELECT * FROM email_queue ORDER BY created_at DESC LIMIT 10");
    if (!empty($recent_emails)) {
        echo "<h3>Recent Emails (Last 10):</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Attempts</th><th>Created</th><th>Sent</th></tr>";
        foreach ($recent_emails as $email) {
            echo "<tr>";
            echo "<td>" . $email['id'] . "</td>";
            echo "<td>" . htmlspecialchars($email['recipient']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($email['subject'], 0, 50)) . "...</td>";
            echo "<td>" . $email['status'] . "</td>";
            echo "<td>" . $email['attempts'] . "</td>";
            echo "<td>" . $email['created_at'] . "</td>";
            echo "<td>" . ($email['sent_at'] ?? 'Not sent') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking queue: " . $e->getMessage() . "</p>";
}

// 3. Check email configuration
echo "<h3>Email Configuration:</h3>";
echo "<p><strong>SMTP Host:</strong> " . (SMTP_HOST ?: 'Not set') . "</p>";
echo "<p><strong>SMTP Port:</strong> " . (SMTP_PORT ?: 'Not set') . "</p>";
echo "<p><strong>SMTP Username:</strong> " . (SMTP_USERNAME ? 'Set' : 'Not set') . "</p>";
echo "<p><strong>SMTP Password:</strong> " . (SMTP_PASSWORD ? 'Set' : 'Not set') . "</p>";
echo "<p><strong>App Email:</strong> " . APP_EMAIL . "</p>";
echo "<p><strong>PHPMailer Available:</strong> " . (defined('PHPMAILER_AVAILABLE') && PHPMAILER_AVAILABLE ? 'Yes' : 'No') . "</p>";

// 4. Test email queueing
echo "<h3>Testing Email Queue Function:</h3>";
$test_result = queue_email('test@example.com', 'Test Email', 'This is a test email from the debug script.');
if ($test_result) {
    echo "<p style='color: green;'>✓ Email queuing function works</p>";
} else {
    echo "<p style='color: red;'>✗ Email queuing function failed</p>";
}

// 5. Check .env file
echo "<h3>Environment Variables:</h3>";
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    echo "<p style='color: green;'>✓ .env file exists</p>";
    $env_content = file_get_contents($env_file);
    $has_smtp = strpos($env_content, 'SMTP_HOST') !== false;
    echo "<p><strong>SMTP configuration in .env:</strong> " . ($has_smtp ? 'Present' : 'Missing') . "</p>";
} else {
    echo "<p style='color: red;'>✗ .env file not found</p>";
}

// 6. Manual email send test
echo "<h3>Manual Email Send Test:</h3>";
echo "<p>Testing direct email send (bypassing queue)...</p>";
$manual_test = send_email('test@example.com', 'Manual Test Email', 'This is a manual test email.');
if ($manual_test) {
    echo "<p style='color: green;'>✓ Manual email send successful</p>";
} else {
    echo "<p style='color: red;'>✗ Manual email send failed</p>";
}

// 7. Check cron job status
echo "<h3>Cron Job Status:</h3>";
$lock_file = __DIR__ . '/cron/send_emails.lock';
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    $time_diff = time() - $lock_time;
    echo "<p><strong>Lock file exists:</strong> Created " . date('Y-m-d H:i:s', $lock_time) . " (" . $time_diff . " seconds ago)</p>";
    if ($time_diff > 300) { // 5 minutes
        echo "<p style='color: orange;'>Warning: Lock file is old, cron job may be stuck</p>";
    }
} else {
    echo "<p>No active cron job lock file</p>";
}

echo "<hr>";
echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>Run the cron job manually: <code>php " . __DIR__ . "/cron/send_queued_emails.php</code></li>";
echo "<li>Check server error logs for email sending errors</li>";
echo "<li>Verify SMTP credentials in .env file</li>";
echo "<li>Test email delivery to a real email address</li>";
echo "</ul>";
?>
