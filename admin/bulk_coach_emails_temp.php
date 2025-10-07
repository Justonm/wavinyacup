<?php
// admin/bulk_coach_emails_temp.php - Temporary bulk email system (no auth check)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Temporary access control
if (!isset($_GET['access']) || $_GET['access'] !== 'temp2025') {
    die('Access denied - use ?access=temp2025');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = db();
$success = '';
$error = '';

// Handle bulk email sending
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
                    FOREIGN KEY (coach_id) REFERENCES coach_registrations(id) ON DELETE CASCADE,
                    INDEX idx_coach_id (coach_id),
                    INDEX idx_email (email)
                )
            ");
            $success = "Coach credentials table created successfully!";
        } catch (Exception $e) {
            $error = "Error creating table: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'generate_credentials') {
        try {
            // Get all approved coaches without coach role
            $coaches = $db->query("
                SELECT cr.*, u.first_name, u.last_name, u.email 
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.status = 'approved' 
                AND u.role != 'coach'
                ORDER BY cr.created_at DESC
            ");
            
            $generated_count = 0;
            $email_count = 0;
            
            foreach ($coaches as $coach) {
                // Generate random password
                $password = generate_random_password();
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user role to coach and generate new password
                $db->query("UPDATE users SET role = 'coach', password_hash = ? WHERE id = ?", [$password_hash, $coach['user_id']]);
                
                // Store credentials in database for reference
                $db->query(
                    "INSERT INTO coach_credentials (coach_id, email, username, password_plain, generated_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    [$coach['id'], $coach['email'], $coach['first_name'] . '_' . $coach['last_name'], $password]
                );
                
                // Queue welcome email
                $email_subject = "Welcome to Wavinyacup - Your Login Credentials";
                $email_body = generate_welcome_email($coach, $coach['first_name'] . '_' . $coach['last_name'], $password);
                
                if (queue_email($coach['email'], $email_subject, $email_body)) {
                    $email_count++;
                }
                
                $generated_count++;
            }
            
            $success = "Generated credentials for {$generated_count} coaches and queued {$email_count} welcome emails.";
            
        } catch (Exception $e) {
            $error = "Error generating credentials: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'send_bulk_emails') {
        try {
            // Process all queued emails manually
            $queued_emails = $db->query("SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC");
            $sent_count = 0;
            $failed_count = 0;
            
            // Load mailer function
            if (file_exists(__DIR__ . '/../includes/mailer.php')) {
                require_once __DIR__ . '/../includes/mailer.php';
            }
            
            foreach ($queued_emails as $email) {
                // Try to send email using available mailer
                $email_sent = false;
                
                if (function_exists('send_email')) {
                    $email_sent = send_email($email['recipient'], $email['subject'], $email['body']);
                } else {
                    // Try direct PHPMailer approach
                    try {
                        require_once __DIR__ . '/../vendor/autoload.php';
                        
                        use PHPMailer\PHPMailer\PHPMailer;
                        use PHPMailer\PHPMailer\SMTP;
                        use PHPMailer\PHPMailer\Exception;
                        
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = $_ENV['SMTP_HOST'];
                        $mail->SMTPAuth = true;
                        $mail->Username = $_ENV['SMTP_USERNAME'];
                        $mail->Password = $_ENV['SMTP_PASSWORD'];
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = $_ENV['SMTP_PORT'];
                        $mail->SMTPDebug = SMTP::DEBUG_OFF;
                        
                        // Relaxed SSL settings for shared hosting
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
                        
                        $email_sent = $mail->send();
                    } catch (Exception $e) {
                        error_log("Direct PHPMailer failed: " . $e->getMessage());
                        $email_sent = false;
                    }
                }
                
                if ($email_sent) {
                    $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$email['id']]);
                    $sent_count++;
                } else {
                    $db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                    $failed_count++;
                }
            }
            
            $success = "Processed {$sent_count} emails successfully, {$failed_count} failed.";
            
        } catch (Exception $e) {
            $error = "Error sending bulk emails: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'resend_all_credentials') {
        try {
            // Get all approved coaches with accounts
            $coaches = $db->query("
                SELECT cr.*, u.username, u.first_name, u.last_name, u.email, cc.password_plain 
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                LEFT JOIN coach_credentials cc ON cr.id = cc.coach_id 
                WHERE cr.status = 'approved' AND u.role = 'coach'
                ORDER BY cr.created_at DESC
            ");
            
            $queued_count = 0;
            
            foreach ($coaches as $coach) {
                // Generate new password if no stored password
                $password = $coach['password_plain'];
                if (!$password) {
                    $password = generate_random_password();
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update user password
                    $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$password_hash, $coach['user_id']]);
                    
                    // Store/update credentials
                    if ($coach['password_plain']) {
                        $db->query("UPDATE coach_credentials SET password_plain = ? WHERE coach_id = ?", [$password, $coach['id']]);
                    } else {
                        $db->query(
                            "INSERT INTO coach_credentials (coach_id, email, username, password_plain, generated_at) 
                             VALUES (?, ?, ?, ?, NOW())",
                            [$coach['id'], $coach['email'], $coach['username'], $password]
                        );
                    }
                }
                
                // Queue welcome email
                $email_subject = "Wavinyacup - Your Login Credentials";
                $email_body = generate_welcome_email($coach, $coach['username'], $password);
                
                if (queue_email($coach['email'], $email_subject, $email_body)) {
                    $queued_count++;
                }
            }
            
            $success = "Queued credentials for {$queued_count} coaches. Click 'Send Bulk Emails' to deliver them.";
            
        } catch (Exception $e) {
            $error = "Error queuing credentials: " . $e->getMessage();
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
$stats = [
    'total_coaches' => $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations WHERE status = 'approved'")['count'],
    'with_accounts' => $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations cr JOIN users u ON cr.user_id = u.id WHERE cr.status = 'approved' AND u.role = 'coach'")['count'],
    'without_accounts' => $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations cr JOIN users u ON cr.user_id = u.id WHERE cr.status = 'approved' AND u.role != 'coach'")['count'],
    'queued_emails' => $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")['count'],
    'failed_emails' => $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'")['count']
];

// Get coaches without accounts
$coaches_without_accounts = $db->query("
    SELECT cr.*, u.first_name, u.last_name, u.email 
    FROM coach_registrations cr 
    JOIN users u ON cr.user_id = u.id 
    WHERE cr.status = 'approved' 
    AND u.role != 'coach'
    ORDER BY cr.created_at DESC
");

// Get coaches with accounts (if table exists)
$coaches_with_accounts = [];
if ($table_exists) {
    $coaches_with_accounts = $db->query("
        SELECT cr.*, u.username, u.first_name, u.last_name, u.email, cc.password_plain, cc.generated_at 
        FROM coach_registrations cr 
        JOIN users u ON cr.user_id = u.id 
        LEFT JOIN coach_credentials cc ON cr.id = cc.coach_id 
        WHERE cr.status = 'approved' AND u.role = 'coach'
        ORDER BY cr.created_at DESC
    ");
}

function generate_random_password($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

function generate_welcome_email($coach, $username, $password) {
    return "
    <html>
    <body>
        <h2>Welcome to Wavinyacup Coach Portal</h2>
        
        <p>Dear {$coach['first_name']} {$coach['last_name']},</p>
        
        <p>Congratulations! Your coach registration has been approved. Below are your login credentials for the Wavinyacup system:</p>
        
        <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <strong>Login Details:</strong><br>
            <strong>Website:</strong> " . ($_ENV['APP_URL'] ?? 'https://governorwavinyacup.com/wavinyacup') . "/coach/dashboard.php<br>
            <strong>Username:</strong> {$username}<br>
            <strong>Password:</strong> {$password}
        </div>
        
        <p><strong>Coach Information:</strong></p>
        <ul>
            <li>License Number: {$coach['license_number']}</li>
            <li>License Level: {$coach['license_level']}</li>
            <li>Team: {$coach['team_name']}</li>
        </ul>
        
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Login to your coach portal using the credentials above</li>
            <li>Complete your team setup</li>
            <li>Add your team players</li>
            <li>Review and submit your final team roster</li>
        </ol>
        
        <p><strong>Important:</strong> Please keep these credentials secure and do not share them with anyone.</p>
        
        <p>If you have any questions or need assistance, please contact us.</p>
        
        <p>Best regards,<br>
        Wavinyacup Administration Team</p>
    </body>
    </html>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Coach Email System (Temporary)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-envelope-bulk"></i> Bulk Coach Email System (Temporary)</h1>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Temporary access - no authentication required
                    </div>
                </div>

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

                <!-- Database Setup -->
                <?php if (!$table_exists): ?>
                <div class="alert alert-info">
                    <h5><i class="fas fa-database"></i> Database Setup Required</h5>
                    <p>The coach_credentials table needs to be created first.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_table">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Database Table
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5><?= $stats['total_coaches'] ?></h5>
                                <p class="mb-0">Total Approved Coaches</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5><?= $stats['with_accounts'] ?></h5>
                                <p class="mb-0">With Login Accounts</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5><?= $stats['without_accounts'] ?></h5>
                                <p class="mb-0">Need Credentials</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5><?= $stats['queued_emails'] ?></h5>
                                <p class="mb-0">Queued Emails</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if ($table_exists): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs"></i> Bulk Actions</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="d-inline-block me-3">
                                    <input type="hidden" name="action" value="generate_credentials">
                                    <button type="submit" class="btn btn-primary" 
                                            onclick="return confirm('Generate credentials for all coaches without accounts?')">
                                        <i class="fas fa-key"></i> Generate All Credentials
                                    </button>
                                </form>

                                <form method="POST" class="d-inline-block me-3">
                                    <input type="hidden" name="action" value="send_bulk_emails">
                                    <button type="submit" class="btn btn-success"
                                            onclick="return confirm('Send all queued emails now?')">
                                        <i class="fas fa-paper-plane"></i> Send Bulk Emails
                                    </button>
                                </form>

                                <form method="POST" class="d-inline-block me-3">
                                    <input type="hidden" name="action" value="resend_all_credentials">
                                    <button type="submit" class="btn btn-warning"
                                            onclick="return confirm('Resend credentials to all existing coaches?')">
                                        <i class="fas fa-redo"></i> Resend All Credentials
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Coaches Without Accounts -->
                <?php if (is_array($coaches_without_accounts) && count($coaches_without_accounts) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus"></i> Coaches Needing Credentials (<?= count($coaches_without_accounts) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Team</th>
                                        <th>License</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coaches_without_accounts as $coach): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></td>
                                        <td><?= htmlspecialchars($coach['email']) ?></td>
                                        <td><?= htmlspecialchars($coach['team_name']) ?></td>
                                        <td><?= htmlspecialchars($coach['license_level'] . ' - ' . $coach['license_number']) ?></td>
                                        <td><?= date('M j, Y', strtotime($coach['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Coaches With Accounts -->
                <?php if (is_array($coaches_with_accounts) && count($coaches_with_accounts) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Coaches With Login Accounts (<?= count($coaches_with_accounts) ?>)</h5>
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
                                    <?php foreach ($coaches_with_accounts as $coach): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></td>
                                        <td><?= htmlspecialchars($coach['email']) ?></td>
                                        <td><code><?= htmlspecialchars($coach['username']) ?></code></td>
                                        <td><code><?= htmlspecialchars($coach['password_plain'] ?? 'N/A') ?></code></td>
                                        <td><?= $coach['generated_at'] ? date('M j, Y', strtotime($coach['generated_at'])) : 'N/A' ?></td>
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
