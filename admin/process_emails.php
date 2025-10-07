<?php
// admin/process_emails.php - Manual email processing for debugging
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check admin access
if (!is_logged_in() || !has_role('admin')) {
    die('Access denied');
}

$db = db();
$message = '';
$emails_processed = 0;
$emails_failed = 0;

if ($_POST['action'] ?? '' === 'process_emails') {
    // Get pending emails
    $emails = $db->fetchAll("
        SELECT * FROM email_queue 
        WHERE status = 'pending' 
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    
    if (empty($emails)) {
        $message = "No pending emails found.";
    } else {
        require_once __DIR__ . '/../includes/mailer.php';
        
        foreach ($emails as $email) {
            if (send_email($email['recipient'], $email['subject'], $email['body'])) {
                $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$email['id']]);
                $emails_processed++;
            } else {
                $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                $emails_failed++;
            }
        }
        
        $message = "Processed: {$emails_processed} sent, {$emails_failed} failed.";
    }
}

// Get current queue status
$queue_stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM email_queue
");

$recent_emails = $db->fetchAll("
    SELECT * FROM email_queue 
    ORDER BY created_at DESC 
    LIMIT 20
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Queue Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Email Queue Management</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Total</h5>
                    <h3><?php echo $queue_stats['total']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Pending</h5>
                    <h3 class="text-warning"><?php echo $queue_stats['pending']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Sent</h5>
                    <h3 class="text-success"><?php echo $queue_stats['sent']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Failed</h5>
                    <h3 class="text-danger"><?php echo $queue_stats['failed']; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <form method="POST" class="mb-4">
        <button type="submit" name="action" value="process_emails" class="btn btn-primary">
            Process Pending Emails (Max 10)
        </button>
    </form>
    
    <h4>Recent Emails</h4>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_emails as $email): ?>
                <tr>
                    <td><?php echo $email['id']; ?></td>
                    <td><?php echo htmlspecialchars($email['recipient']); ?></td>
                    <td><?php echo htmlspecialchars(substr($email['subject'], 0, 50)); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $email['status'] === 'sent' ? 'success' : 
                                ($email['status'] === 'failed' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo $email['status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y H:i', strtotime($email['created_at'])); ?></td>
                    <td><?php echo $email['sent_at'] ? date('M j, Y H:i', strtotime($email['sent_at'])) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
