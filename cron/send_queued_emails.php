<?php
// cron/send_queued_emails.php - Processes and sends emails from the queue.
// This script should be run by a cron job (e.g., every minute).

// Set a longer execution time, as sending emails can be slow.
set_time_limit(300); // 5 minutes

// Define a lock file to prevent multiple instances from running simultaneously.
$lock_file = __DIR__ . '/send_emails.lock';
$lock_handle = fopen($lock_file, 'w');

// Try to acquire an exclusive, non-blocking lock.
if (!$lock_handle || !flock($lock_handle, LOCK_EX | LOCK_NB)) {
    echo "Cron job is already running. Exiting.\n";
    exit;
}

// Bootstrap the application
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

// Set timezone to avoid warnings
date_default_timezone_set('UTC'); 

echo "Starting email queue processing at " . date('Y-m-d H:i:s') . "...\n";

$db = db();
$emails_sent = 0;
$emails_failed = 0;

try {
    // Fetch a batch of pending emails to process (e.g., 20 at a time).
    // Also fetch emails that have failed but not too many times (e.g., less than 5 attempts).
    $emails = $db->fetchAll("
        SELECT * FROM email_queue 
        WHERE (status = 'pending' OR status = 'failed') 
        AND attempts < 5
        ORDER BY created_at ASC 
        LIMIT 20
    ");

    if (empty($emails)) {
        echo "No pending emails to send. Exiting.\n";
    } else {
        echo "Found " . count($emails) . " emails to process.\n";
    }

    foreach ($emails as $email) {
        echo "Processing email ID {$email['id']} to {$email['recipient']}... ";
        
        if (send_email($email['recipient'], $email['subject'], $email['body'])) {
            // Email sent successfully
            $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$email['id']]);
            $emails_sent++;
            echo "Success.\n";
        } else {
            // Email failed to send
            $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
            $emails_failed++;
            echo "Failed.\n";
            // Log this failure more permanently if needed
            error_log("Email queue: Failed to send email ID {$email['id']} to {$email['recipient']}");
        }
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
    error_log("Email queue cron job failed: " . $e->getMessage());
} finally {
    // Release the lock and close the file handle
    flock($lock_handle, LOCK_UN);
    fclose($lock_handle);
}

echo "Email queue processing finished. Sent: {$emails_sent}, Failed: {$emails_failed}.\n";
exit;
