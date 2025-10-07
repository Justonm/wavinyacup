<?php
// simple_bulk_email.php - Simple working bulk email system
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['access']) || $_GET['access'] !== 'simple2025') {
    die('Access denied - use ?access=simple2025');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = db();
$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_table') {
        try {
            $db->query("
                CREATE TABLE IF NOT EXISTS coach_credentials (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    coach_id INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    username VARCHAR(100) NOT NULL,
                    password_plain VARCHAR(255) NOT NULL,
                    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_coach_id (coach_id),
                    INDEX idx_email (email)
                )
            ");
            $success = "Coach credentials table created successfully!";
        } catch (Exception $e) {
            $error = "Error creating table: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'queue_all_emails') {
        try {
            // Get all approved coaches
            $coaches = $db->query("
                SELECT cr.*, u.username, u.first_name, u.last_name, u.email 
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.status = 'approved' AND u.role = 'coach'
                ORDER BY cr.created_at DESC
            ");
            
            $queued_count = 0;
            
            foreach ($coaches as $coach) {
                // Generate new password
                $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user password
                $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$password_hash, $coach['user_id']]);
                
                // Store credentials
                $db->query("DELETE FROM coach_credentials WHERE coach_id = ?", [$coach['id']]);
                $db->query(
                    "INSERT INTO coach_credentials (coach_id, email, username, password_plain, generated_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    [$coach['id'], $coach['email'], $coach['username'], $password]
                );
                
                // Create email content
                $email_subject = "Wavinyacup - Your Login Credentials";
                $email_body = "
                <html>
                <body>
                    <h2>Welcome to Wavinyacup Coach Portal</h2>
                    
                    <p>Dear {$coach['first_name']} {$coach['last_name']},</p>
                    
                    <p>Your login credentials for the Wavinyacup system:</p>
                    
                    <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <strong>Login Details:</strong><br>
                        <strong>Website:</strong> https://governorwavinyacup.com/wavinyacup/coach/dashboard.php<br>
                        <strong>Username:</strong> {$coach['username']}<br>
                        <strong>Password:</strong> {$password}
                    </div>
                    
                    <p>Please keep these credentials secure.</p>
                    
                    <p>Best regards,<br>
                    Wavinyacup Administration Team</p>
                </body>
                </html>";
                
                // Queue email
                if (queue_email($coach['email'], $email_subject, $email_body)) {
                    $queued_count++;
                }
            }
            
            $success = "Queued credentials for {$queued_count} coaches. Click 'Send All Emails' to deliver them.";
            
        } catch (Exception $e) {
            $error = "Error queuing emails: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'send_all_emails') {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\SMTP;
            use PHPMailer\PHPMailer\Exception;
            
            // Get queued emails
            $queued_emails = $db->query("SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC");
            $sent_count = 0;
            $failed_count = 0;
            
            foreach ($queued_emails as $email) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $_ENV['SMTP_HOST'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['SMTP_USERNAME'];
                    $mail->Password = $_ENV['SMTP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $_ENV['SMTP_PORT'];
                    $mail->SMTPDebug = SMTP::DEBUG_OFF;
                    
                    // Relaxed SSL settings
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
                        )
                    );
                    
                    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
                    $mail->addAddress($email['recipient']);
                    $mail->Subject = $email['subject'];
                    $mail->Body = $email['body'];
                    $mail->isHTML(true);
                    
                    $mail->send();
                    $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$email['id']]);
                    $sent_count++;
                    
                } catch (Exception $e) {
                    $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                    $failed_count++;
                    error_log("Email failed: " . $e->getMessage());
                }
            }
            
            $success = "Sent {$sent_count} emails successfully, {$failed_count} failed.";
            
        } catch (Exception $e) {
            $error = "Error sending emails: " . $e->getMessage();
        }
    }
}

// Check if table exists
$table_exists = false;
try {
    $db->query("SELECT 1 FROM coach_credentials LIMIT 1");
    $table_exists = true;
} catch (Exception $e) {
    // Table doesn't exist
}

// Get statistics
$stats = array(
    'total_coaches' => 0,
    'queued_emails' => 0,
    'failed_emails' => 0
);

try {
    $stats['total_coaches'] = $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations WHERE status = 'approved'")['count'];
    $stats['queued_emails'] = $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")['count'];
    $stats['failed_emails'] = $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'")['count'];
} catch (Exception $e) {
    // Ignore errors
}

// Get coaches
$coaches = array();
try {
    $coaches = $db->query("
        SELECT cr.*, u.username, u.first_name, u.last_name, u.email, cc.password_plain, cc.generated_at 
        FROM coach_registrations cr 
        JOIN users u ON cr.user_id = u.id 
        LEFT JOIN coach_credentials cc ON cr.id = cc.coach_id 
        WHERE cr.status = 'approved' AND u.role = 'coach'
        ORDER BY cr.created_at DESC
    ");
} catch (Exception $e) {
    // Ignore errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Bulk Email System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-envelope-bulk"></i> Simple Bulk Email System</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5><?= $stats['total_coaches'] ?></h5>
                                <p class="mb-0">Total Approved Coaches</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5><?= $stats['queued_emails'] ?></h5>
                                <p class="mb-0">Queued Emails</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5><?= $stats['failed_emails'] ?></h5>
                                <p class="mb-0">Failed Emails</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$table_exists): ?>
                            <form method="POST" class="d-inline-block me-3">
                                <input type="hidden" name="action" value="create_table">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-database"></i> Create Database Table
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-inline-block me-3">
                                <input type="hidden" name="action" value="queue_all_emails">
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Queue credentials for all coaches?')">
                                    <i class="fas fa-plus"></i> Queue All Credentials
                                </button>
                            </form>

                            <form method="POST" class="d-inline-block me-3">
                                <input type="hidden" name="action" value="send_all_emails">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Send all queued emails now?')">
                                    <i class="fas fa-paper-plane"></i> Send All Emails
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coaches List -->
                <?php if (count($coaches) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Coaches (<?= count($coaches) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Generated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coaches as $coach): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></td>
                                        <td><?= htmlspecialchars($coach['email']) ?></td>
                                        <td><code><?= htmlspecialchars($coach['username']) ?></code></td>
                                        <td><code><?= htmlspecialchars($coach['password_plain'] ?? 'N/A') ?></code></td>
                                        <td><?= $coach['generated_at'] ? date('M j, Y H:i', strtotime($coach['generated_at'])) : 'N/A' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
