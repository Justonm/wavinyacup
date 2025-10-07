<?php
// admin/bulk_coach_emails.php - Bulk email system for coach credentials
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/admin_login.php');
    exit;
}

$db = db();
$success = '';
$error = '';

// Handle bulk email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_credentials') {
        try {
            // Get all approved coaches without user accounts
            $coaches = $db->query("
                SELECT cr.*, c.* 
                FROM coach_registrations cr 
                LEFT JOIN coaches c ON cr.id = c.registration_id 
                LEFT JOIN users u ON cr.email = u.email 
                WHERE cr.status = 'approved' 
                AND u.id IS NULL
                ORDER BY cr.created_at DESC
            ");
            
            $generated_count = 0;
            $email_count = 0;
            
            foreach ($coaches as $coach) {
                // Generate random password
                $password = generate_random_password();
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Create user account
                $user_id = $db->query(
                    "INSERT INTO users (username, email, password_hash, role, is_active, created_at) 
                     VALUES (?, ?, ?, 'coach', 1, NOW())",
                    [$coach['first_name'] . '_' . $coach['last_name'], $coach['email'], $password_hash]
                );
                
                if ($user_id) {
                    // Update coach record with user_id
                    $db->query("UPDATE coaches SET user_id = ? WHERE registration_id = ?", [$user_id, $coach['id']]);
                    
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
            }
            
            $success = "Generated credentials for {$generated_count} coaches and queued {$email_count} welcome emails.";
            
        } catch (Exception $e) {
            $error = "Error generating credentials: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'send_bulk_emails') {
        try {
            // Process all queued emails
            require_once __DIR__ . '/../cron/send_queued_emails.php';
            $success = "Bulk email sending initiated. Check the email queue status below.";
        } catch (Exception $e) {
            $error = "Error sending bulk emails: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'resend_credentials') {
        $coach_ids = $_POST['coach_ids'] ?? [];
        $resent_count = 0;
        
        foreach ($coach_ids as $coach_id) {
            $coach = $db->fetchRow("
                SELECT cr.*, c.*, cc.username, cc.password_plain 
                FROM coach_registrations cr 
                JOIN coaches c ON cr.id = c.registration_id 
                LEFT JOIN coach_credentials cc ON cr.id = cc.coach_id 
                WHERE cr.id = ?", [$coach_id]);
            
            if ($coach && $coach['username']) {
                $email_subject = "Wavinyacup - Your Login Credentials (Resent)";
                $email_body = generate_welcome_email($coach, $coach['username'], $coach['password_plain']);
                
                if (queue_email($coach['email'], $email_subject, $email_body)) {
                    $resent_count++;
                }
            }
        }
        
        $success = "Resent credentials to {$resent_count} coaches.";
    }
}

// Get statistics
$stats = [
    'total_coaches' => $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations WHERE status = 'approved'")['count'],
    'with_accounts' => $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations cr JOIN users u ON cr.email = u.email WHERE cr.status = 'approved'")['count'],
    'without_accounts' => $db->fetchRow("SELECT COUNT(*) as count FROM coach_registrations cr LEFT JOIN users u ON cr.email = u.email WHERE cr.status = 'approved' AND u.id IS NULL")['count'],
    'queued_emails' => $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")['count'],
    'failed_emails' => $db->fetchRow("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'")['count']
];

// Get coaches without accounts
$coaches_without_accounts = $db->query("
    SELECT cr.*, c.* 
    FROM coach_registrations cr 
    LEFT JOIN coaches c ON cr.id = c.registration_id 
    LEFT JOIN users u ON cr.email = u.email 
    WHERE cr.status = 'approved' 
    AND u.id IS NULL
    ORDER BY cr.created_at DESC
");

// Get coaches with accounts
$coaches_with_accounts = $db->query("
    SELECT cr.*, c.*, u.username, cc.password_plain, cc.generated_at 
    FROM coach_registrations cr 
    JOIN coaches c ON cr.id = c.registration_id 
    JOIN users u ON cr.email = u.email 
    LEFT JOIN coach_credentials cc ON cr.id = cc.coach_id 
    WHERE cr.status = 'approved' 
    ORDER BY cr.created_at DESC
");

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
            <strong>Website:</strong> " . $_ENV['APP_URL'] . "/coach/dashboard.php<br>
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
        
        <p>If you have any questions or need assistance, please contact us at " . $_ENV['ADMIN_EMAILS'] . "</p>
        
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
    <title>Bulk Coach Email System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-envelope-bulk"></i> Bulk Coach Email System</h1>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
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

                                <a href="../admin/process_emails.php" class="btn btn-info">
                                    <i class="fas fa-list"></i> View Email Queue
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coaches Without Accounts -->
                <?php if (count($coaches_without_accounts) > 0): ?>
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
                <?php if (count($coaches_with_accounts) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Coaches With Login Accounts (<?= count($coaches_with_accounts) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="resend_credentials">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all"></th>
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
                                            <td><input type="checkbox" name="coach_ids[]" value="<?= $coach['id'] ?>"></td>
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
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-redo"></i> Resend Selected Credentials
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="coach_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
