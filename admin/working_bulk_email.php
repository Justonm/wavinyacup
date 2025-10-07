<?php
// working_bulk_email.php - Working email system using existing queue
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'working2025') {
    die('Access denied - use ?access=working2025');
}

$message = '';
$coaches = [];

// Load environment variables manually
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}", 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Define queue_email function directly to avoid include issues
function queue_email($to, $subject, $body, $from_name = 'Wavinyacup System') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (recipient_email, subject, body, from_name, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([$to, $subject, $body, $from_name]);
    } catch (PDOException $e) {
        error_log("Failed to queue email: " . $e->getMessage());
        return false;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_coaches') {
        try {
            $stmt = $pdo->query("
                SELECT cr.id, cr.user_id, u.first_name, u.last_name, u.email, u.username, cr.status, u.role
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.status = 'approved'
                ORDER BY cr.created_at DESC
            ");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $message = "Found " . count($coaches) . " approved coaches";
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
    
    if ($action === 'queue_credentials') {
        try {
            // Get all approved coaches
            $stmt = $pdo->query("
                SELECT cr.id, cr.user_id, u.first_name, u.last_name, u.email, u.username 
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.status = 'approved'
            ");
            $all_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $queued_count = 0;
            
            foreach ($all_coaches as $coach) {
                // Generate new password
                $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user password
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $coach['user_id']]);
                
                // Create email
                $email_subject = "Wavinyacup - Your Login Credentials";
                $email_body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>Wavinyacup Login Credentials</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1 style='margin: 0; font-size: 28px;'>🏆 Wavinyacup</h1>
                        <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Coach Portal Access</p>
                    </div>
                    
                    <div style='background: white; padding: 30px; border: 1px solid #e9ecef; border-top: none; border-radius: 0 0 10px 10px;'>
                        <p style='font-size: 18px; margin-bottom: 20px;'>Dear <strong>{$coach['first_name']} {$coach['last_name']}</strong>,</p>
                        
                        <p>Your login credentials for the Wavinyacup coaching system have been generated:</p>
                        
                        <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 5px solid #007bff;'>
                            <h3 style='margin-top: 0; color: #495057;'>🔐 Login Details</h3>
                            <p><strong>Website:</strong> <a href='https://governorwavinyacup.com/wavinyacup/coach/dashboard.php'>Coach Dashboard</a></p>
                            <p><strong>Username:</strong> <code style='background: #e9ecef; padding: 4px 8px; border-radius: 4px;'>{$coach['username']}</code></p>
                            <p><strong>Password:</strong> <code style='background: #e9ecef; padding: 4px 8px; border-radius: 4px;'>{$password}</code></p>
                        </div>
                        
                        <div style='background: #fff3cd; padding: 20px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 25px 0;'>
                            <p style='margin: 0; color: #856404;'><strong>⚠️ Important:</strong> Please keep these credentials secure and change your password after first login.</p>
                        </div>
                        
                        <p>Best regards,<br><strong>Wavinyacup Administration Team</strong></p>
                    </div>
                    
                    <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                        <p>Generated on " . date('F j, Y \a\t g:i A') . "</p>
                    </div>
                </body>
                </html>";
                
                // Queue email using existing system
                if (queue_email($coach['email'], $email_subject, $email_body)) {
                    $queued_count++;
                }
            }
            
            $message = "✅ Queued credentials for {$queued_count} coaches. Emails will be sent by the system's cron job.";
            
        } catch (Exception $e) {
            $message = "❌ Error queuing credentials: " . $e->getMessage();
        }
    }
    
    if ($action === 'check_queue') {
        try {
            $pending = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")->fetch()['count'];
            $sent = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'sent'")->fetch()['count'];
            $failed = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'")->fetch()['count'];
            
            $message = "📊 Email Queue Status: Pending: {$pending}, Sent: {$sent}, Failed: {$failed}";
        } catch (Exception $e) {
            $message = "❌ Error checking queue: " . $e->getMessage();
        }
    }
    
    if ($action === 'process_queue') {
        try {
            // Trigger the cron job manually by including the processing script
            $cron_file = dirname(__DIR__) . '/cron/send_queued_emails.php';
            if (file_exists($cron_file)) {
                ob_start();
                include $cron_file;
                $output = ob_get_clean();
                $message = "✅ Queue processing triggered. Check email queue status for results.";
            } else {
                $message = "❌ Cron job file not found. Emails will be processed by the server's scheduled task.";
            }
        } catch (Exception $e) {
            $message = "❌ Error processing queue: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Working Bulk Email System</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f8f9fa; min-height: 100vh;
        }
        .container {
            max-width: 1200px; margin: 0 auto; background: white; padding: 30px;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; font-size: 2.5em; }
        .btn { 
            padding: 14px 28px; margin: 8px; background: #007bff; color: white; border: none;
            cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: 500;
            transition: all 0.3s ease; display: inline-block; text-decoration: none;
        }
        .btn:hover { background: #0056b3; transform: translateY(-2px); }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .message { 
            padding: 20px; margin: 25px 0; border-radius: 8px; font-size: 16px; font-weight: 500;
            background: #d4edda; color: #155724; border-left: 5px solid #28a745;
        }
        .error { background: #f8d7da; color: #721c24; border-left-color: #dc3545; }
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin: 30px 0;
        }
        .stat-card {
            padding: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border-radius: 10px; text-align: center;
        }
        .stat-number { font-size: 2.5em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
        .actions { text-align: center; margin: 40px 0; padding: 30px; background: #f8f9fa; border-radius: 10px; }
        .coaches-table {
            width: 100%; border-collapse: collapse; margin-top: 25px; background: white;
            border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .coaches-table th, .coaches-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .coaches-table th { background: #f8f9fa; font-weight: 600; color: #495057; }
        .coaches-table tr:hover { background: #f8f9fa; }
        .info-box {
            background: #e7f3ff; border-left: 4px solid #007bff; padding: 20px; margin: 20px 0; border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Working Bulk Email System</h1>
        
        <div class="info-box">
            <h4>📧 Email Method: Queue System</h4>
            <p>This system uses your existing email queue and cron job system to send emails reliably.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, 'error') || str_contains($message, 'Error') || str_contains($message, '❌') ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($coaches) ?></div>
                <div class="stat-label">Coaches Found</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= date('H:i') ?></div>
                <div class="stat-label">Current Time</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">📨</div>
                <div class="stat-label">Queue System</div>
            </div>
        </div>
        
        <div class="actions">
            <h3>📧 Email Actions</h3>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="get_coaches">
                <button type="submit" class="btn">📋 Get Coaches</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="queue_credentials">
                <button type="submit" class="btn btn-warning" onclick="return confirm('⚠️ This will generate NEW passwords for ALL coaches and queue them for email delivery. Continue?')">
                    📤 Queue Credentials
                </button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="check_queue">
                <button type="submit" class="btn btn-info">📊 Check Queue Status</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="process_queue">
                <button type="submit" class="btn btn-success">⚡ Process Queue Now</button>
            </form>
        </div>
        
        <?php if (count($coaches) > 0): ?>
        <h3>👥 Approved Coaches (<?= count($coaches) ?>)</h3>
        <table class="coaches-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coaches as $coach): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></strong></td>
                    <td><?= htmlspecialchars($coach['email']) ?></td>
                    <td><code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($coach['username']) ?></code></td>
                    <td><span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= htmlspecialchars($coach['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4>📝 How This Works:</h4>
            <ol>
                <li><strong>Get Coaches</strong> - Shows all approved coaches in your system</li>
                <li><strong>Queue Credentials</strong> - Generates new passwords and adds emails to the queue</li>
                <li><strong>Check Queue Status</strong> - Shows pending, sent, and failed email counts</li>
                <li><strong>Process Queue Now</strong> - Manually triggers email sending (if cron job exists)</li>
            </ol>
            <p><em>Note: Your system has a cron job that automatically processes queued emails. The emails will be sent even if manual processing fails.</em></p>
        </div>
    </div>
</body>
</html>
