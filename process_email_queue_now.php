<?php
/**
 * Process Email Queue Immediately
 * This script processes the email queue without the lock file restrictions
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

echo "<h2>Processing Email Queue Now</h2>";

$db = db();
$emails_sent = 0;
$emails_failed = 0;

try {
    // Get all pending emails
    $emails = $db->fetchAll("
        SELECT * FROM email_queue 
        WHERE status = 'pending' 
        ORDER BY created_at ASC
    ");

    if (empty($emails)) {
        echo "<p>No pending emails found.</p>";
    } else {
        echo "<p>Found " . count($emails) . " pending email(s). Processing...</p>";
        
        foreach ($emails as $email) {
            echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; background: #f9f9f9;'>";
            echo "<h4>Processing Email ID: " . $email['id'] . "</h4>";
            echo "<p><strong>To:</strong> " . htmlspecialchars($email['recipient']) . "</p>";
            echo "<p><strong>Subject:</strong> " . htmlspecialchars($email['subject']) . "</p>";
            echo "<p><strong>Created:</strong> " . $email['created_at'] . "</p>";
            
            echo "<p>Sending email...</p>";
            
            if (send_email($email['recipient'], $email['subject'], $email['body'])) {
                // Update status to sent
                $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                echo "<p style='color: green; font-weight: bold;'>✓ EMAIL SENT SUCCESSFULLY!</p>";
                $emails_sent++;
            } else {
                // Update status to failed
                $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                echo "<p style='color: red; font-weight: bold;'>✗ EMAIL FAILED TO SEND</p>";
                $emails_failed++;
            }
            echo "</div>";
        }
    }

    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<p><strong>Emails sent:</strong> " . $emails_sent . "</p>";
    echo "<p><strong>Emails failed:</strong> " . $emails_failed . "</p>";

    if ($emails_sent > 0) {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Email processing completed! Check your inbox.</strong></p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='debug_email_system.php'>View Email System Debug</a></p>";
?>
