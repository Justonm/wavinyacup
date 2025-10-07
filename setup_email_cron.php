<?php
// Setup script for email cron job in production
// Run this once to set up the email system properly

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Email System Setup for Production</h2>\n";

$db = db();

// 1. Create email_queue table if it doesn't exist
echo "<h3>1. Setting up Email Queue Table</h3>\n";
try {
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(255) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        body TEXT NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL,
        INDEX idx_status_attempts (status, attempts),
        INDEX idx_created_at (created_at)
    )";
    
    $db->query($create_table_sql);
    echo "<p>✅ Email queue table created/verified successfully</p>\n";
    
    // Check current queue status
    $pending = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    $failed = $db->fetchColumn("SELECT COUNT(*) FROM email_queue WHERE status = 'failed' AND attempts < 5");
    $total = $db->fetchColumn("SELECT COUNT(*) FROM email_queue");
    
    echo "<p>📊 Queue Status: {$total} total, {$pending} pending, {$failed} failed (retryable)</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error setting up email queue: " . $e->getMessage() . "</p>\n";
}

// 2. Test SMTP configuration
echo "<h3>2. SMTP Configuration Check</h3>\n";
$smtp_config = [
    'SMTP_HOST' => $_ENV['SMTP_HOST'] ?? null,
    'SMTP_PORT' => $_ENV['SMTP_PORT'] ?? null,
    'SMTP_USERNAME' => $_ENV['SMTP_USERNAME'] ?? null,
    'SMTP_PASSWORD' => $_ENV['SMTP_PASSWORD'] ?? null,
];

$config_ok = true;
foreach ($smtp_config as $key => $value) {
    if (empty($value)) {
        echo "<p>❌ {$key} is not set in .env file</p>\n";
        $config_ok = false;
    } else {
        echo "<p>✅ {$key} is configured</p>\n";
    }
}

if ($config_ok) {
    echo "<p>✅ SMTP configuration appears complete</p>\n";
} else {
    echo "<p>❌ Please check your .env file configuration</p>\n";
}

// 3. Generate cron job commands
echo "<h3>3. Cron Job Setup Instructions</h3>\n";

$php_path = exec('which php') ?: '/usr/bin/php';
$script_path = __DIR__ . '/cron/send_queued_emails.php';

echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>\n";
echo "<h4>For cPanel/Shared Hosting:</h4>\n";
echo "<p>Add this cron job in your hosting control panel:</p>\n";
echo "<code>* * * * * {$php_path} {$script_path}</code>\n";
echo "<p><small>This runs every minute. Adjust frequency as needed.</small></p>\n";

echo "<h4>For VPS/Dedicated Server:</h4>\n";
echo "<p>Run: <code>crontab -e</code> and add:</p>\n";
echo "<code>* * * * * {$php_path} {$script_path} >> /tmp/email_cron.log 2>&1</code>\n";

echo "<h4>Alternative - Every 5 minutes:</h4>\n";
echo "<code>*/5 * * * * {$php_path} {$script_path}</code>\n";
echo "</div>\n";

// 4. Test manual execution
echo "<h3>4. Manual Test</h3>\n";
if (isset($_GET['run_cron'])) {
    echo "<p>🔄 Running email queue processor manually...</p>\n";
    
    // Capture output from the cron script
    ob_start();
    include __DIR__ . '/cron/send_queued_emails.php';
    $output = ob_get_clean();
    
    echo "<pre style='background: #f0f0f0; padding: 10px;'>{$output}</pre>\n";
}

echo "<p><a href='?run_cron=1' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Run Email Queue Now</a></p>\n";

// 5. Monitoring suggestions
echo "<h3>5. Monitoring & Troubleshooting</h3>\n";
echo "<ul>\n";
echo "<li>Check server error logs for email failures</li>\n";
echo "<li>Monitor the email_queue table for stuck emails</li>\n";
echo "<li>Verify Gmail app password is still valid</li>\n";
echo "<li>Test with: <a href='test_email_queue.php'>test_email_queue.php</a></li>\n";
echo "</ul>\n";

// 6. Quick fixes for common issues
echo "<h3>6. Common Issues & Fixes</h3>\n";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>\n";
echo "<h4>If emails are stuck in 'pending':</h4>\n";
echo "<ul>\n";
echo "<li>Cron job is not running - check cron setup</li>\n";
echo "<li>SMTP authentication failing - verify Gmail app password</li>\n";
echo "<li>Server firewall blocking port 465 - contact hosting provider</li>\n";
echo "</ul>\n";

echo "<h4>If you see 'failed' emails:</h4>\n";
echo "<ul>\n";
echo "<li>Check error logs for specific failure reasons</li>\n";
echo "<li>Gmail may have rate limits - reduce cron frequency</li>\n";
echo "<li>Recipient email addresses may be invalid</li>\n";
echo "</ul>\n";
echo "</div>\n";

?>
