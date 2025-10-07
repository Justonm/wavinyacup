<?php
/**
 * Manual Email Queue Test
 * This script manually processes the email queue to test email sending
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

echo "<h2>Manual Email Queue Processing</h2>";

$db = db();

try {
    // Check if email_queue table exists, create if not
    $tables = $db->fetchAll("SHOW TABLES LIKE 'email_queue'");
    if (empty($tables)) {
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
        echo "<p style='color: green;'>Email queue table created!</p>";
    }

    // Get pending emails
    $pending_emails = $db->fetchAll("
        SELECT * FROM email_queue 
        WHERE status = 'pending' 
        ORDER BY created_at ASC 
        LIMIT 10
    ");

    if (empty($pending_emails)) {
        echo "<p>No pending emails found in queue.</p>";
        
        // Add a test email to queue
        echo "<p>Adding a test email to queue...</p>";
        $test_result = queue_email('admin@governorwavinyacup.com', 'Test Email Queue', 'This is a test email to verify the queue system is working.');
        
        if ($test_result) {
            echo "<p style='color: green;'>Test email added to queue successfully!</p>";
            // Fetch it again
            $pending_emails = $db->fetchAll("
                SELECT * FROM email_queue 
                WHERE status = 'pending' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
        }
    }

    if (!empty($pending_emails)) {
        echo "<p>Found " . count($pending_emails) . " pending email(s). Processing...</p>";
        
        foreach ($pending_emails as $email) {
            echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 10px;'>";
            echo "<h4>Email ID: " . $email['id'] . "</h4>";
            echo "<p><strong>To:</strong> " . htmlspecialchars($email['recipient']) . "</p>";
            echo "<p><strong>Subject:</strong> " . htmlspecialchars($email['subject']) . "</p>";
            echo "<p><strong>Created:</strong> " . $email['created_at'] . "</p>";
            echo "<p><strong>Attempts:</strong> " . $email['attempts'] . "</p>";
            
            echo "<p>Attempting to send...</p>";
            
            if (send_email($email['recipient'], $email['subject'], $email['body'])) {
                // Update status to sent
                $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                echo "<p style='color: green;'><strong>✓ Email sent successfully!</strong></p>";
            } else {
                // Update status to failed
                $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                echo "<p style='color: red;'><strong>✗ Email failed to send!</strong></p>";
            }
            echo "</div>";
        }
    }

    // Show queue statistics
    echo "<h3>Queue Statistics:</h3>";
    $stats = $db->fetchRow("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM email_queue
    ");
    
    echo "<ul>";
    echo "<li><strong>Total emails:</strong> " . $stats['total'] . "</li>";
    echo "<li><strong>Pending:</strong> " . $stats['pending'] . "</li>";
    echo "<li><strong>Sent:</strong> " . $stats['sent'] . "</li>";
    echo "<li><strong>Failed:</strong> " . $stats['failed'] . "</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='debug_email_system.php'>← Back to Email Debug</a></p>";
?>
