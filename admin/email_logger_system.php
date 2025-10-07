<?php
// email_logger_system.php - Email logging and manual sending system
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'emaillog2025') {
    die('Access denied - use ?access=emaillog2025');
}

// Load environment variables
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

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_credentials') {
        try {
            // Get all approved coaches without credentials
            $stmt = $pdo->query("
                SELECT u.id, u.email, u.first_name, u.last_name, cr.id as coach_id
                FROM users u 
                JOIN coach_registrations cr ON u.id = cr.user_id
                WHERE cr.status = 'approved' 
                AND (u.password_hash IS NULL OR u.password_hash = '' OR u.temp_password IS NULL OR u.temp_password = '')
                ORDER BY u.first_name, u.last_name
            ");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $credentials_generated = 0;
            $log_entries = [];
            
            foreach ($coaches as $coach) {
                // Generate random password
                $password = bin2hex(random_bytes(4)); // 8 character password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user password
                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, temp_password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $password, $coach['id']]);
                
                // Create email content
                $subject = "Your Coach Portal Login Credentials - Wavinyacup";
                $body = "
                <html>
                <body style='font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa;'>
                    <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
                        <h2 style='color: #2c3e50; text-align: center; margin-bottom: 30px;'>🏆 Welcome to Wavinyacup Coach Portal</h2>
                        
                        <p>Dear Coach {$coach['first_name']} {$coach['last_name']},</p>
                        
                        <p>Your coach registration has been approved! Here are your login credentials for the coach portal:</p>
                        
                        <div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff;'>
                            <p><strong>🌐 Portal URL:</strong> <a href='https://governorwavinyacup.com/wavinyacup/auth/coach_login.php'>https://governorwavinyacup.com/wavinyacup/auth/coach_login.php</a></p>
                            <p><strong>📧 Email:</strong> {$coach['email']}</p>
                            <p><strong>🔑 Password:</strong> <span style='font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-size: 16px; font-weight: bold;'>{$password}</span></p>
                        </div>
                        
                        <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                            <p><strong>⚠️ Important Security Notes:</strong></p>
                            <ul>
                                <li>Please change your password after your first login</li>
                                <li>Keep your credentials secure and do not share them</li>
                                <li>Contact support if you have any login issues</li>
                            </ul>
                        </div>
                        
                        <p>Through the coach portal, you can:</p>
                        <ul>
                            <li>✅ Manage your team information</li>
                            <li>👥 View and update player details</li>
                            <li>📊 Access tournament schedules and results</li>
                            <li>📱 Update your contact information</li>
                        </ul>
                        
                        <p style='margin-top: 30px;'>Welcome to the Wavinyacup family! We look forward to an exciting tournament.</p>
                        
                        <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;'>
                            <p style='color: #6c757d; font-size: 14px;'>
                                Best regards,<br>
                                <strong>Wavinyacup Tournament Committee</strong><br>
                                📧 support@governorwavinyacup.com
                            </p>
                        </div>
                    </div>
                </body>
                </html>";
                
                // Log the email for manual sending
                $log_entries[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'coach_name' => $coach['first_name'] . ' ' . $coach['last_name'],
                    'email' => $coach['email'],
                    'subject' => $subject,
                    'body' => $body,
                    'password' => $password
                ];
                
                $credentials_generated++;
            }
            
            // Save to log file
            if (!empty($log_entries)) {
                $log_file = dirname(__DIR__) . '/logs/coach_credentials_' . date('Y-m-d_H-i-s') . '.json';
                $log_dir = dirname($log_file);
                if (!is_dir($log_dir)) {
                    mkdir($log_dir, 0755, true);
                }
                file_put_contents($log_file, json_encode($log_entries, JSON_PRETTY_PRINT));
                
                $message = "✅ Generated credentials for {$credentials_generated} coaches and saved to log file for manual sending.";
            } else {
                $message = "ℹ️ No coaches found that need credentials.";
            }
            
        } catch (Exception $e) {
            $message = "❌ Error generating credentials: " . $e->getMessage();
        }
    }
    
    if ($action === 'mark_emails_sent') {
        try {
            // Update email queue to mark as sent
            $stmt = $pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE status = 'pending' OR status = 'failed'");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $message = "✅ Marked {$affected} emails as sent in the queue.";
        } catch (Exception $e) {
            $message = "❌ Error updating email queue: " . $e->getMessage();
        }
    }
}

// Get coaches needing credentials
$coaches_needing_credentials = [];
try {
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.first_name, u.last_name, cr.status,
               CASE WHEN u.password_hash IS NULL OR u.password_hash = '' THEN 'No' ELSE 'Yes' END as has_password
        FROM users u 
        JOIN coach_registrations cr ON u.id = cr.user_id
        WHERE cr.status = 'approved'
        ORDER BY u.first_name, u.last_name
    ");
    $coaches_needing_credentials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignore errors
}

// Get email queue stats
$queue_stats = [];
try {
    $queue_stats['pending'] = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")->fetch()['count'];
    $queue_stats['failed'] = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'")->fetch()['count'];
} catch (Exception $e) {
    $queue_stats = ['pending' => 0, 'failed' => 0];
}

// Get log files
$log_files = [];
$log_dir = dirname(__DIR__) . '/logs';
if (is_dir($log_dir)) {
    $files = glob($log_dir . '/coach_credentials_*.json');
    foreach ($files as $file) {
        $log_files[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'modified' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    usort($log_files, function($a, $b) {
        return $b['modified'] <=> $a['modified'];
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logger System</title>
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
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin: 30px 0;
        }
        .stat-card {
            padding: 25px; border-radius: 10px; text-align: center; color: white;
        }
        .stat-card.coaches { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.failed { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-number { font-size: 2.5em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; }
        .btn { 
            padding: 12px 24px; margin: 8px; background: #007bff; color: white; border: none;
            cursor: pointer; border-radius: 6px; font-size: 14px; transition: all 0.3s ease;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .message { 
            padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 14px;
            background: #d4edda; color: #155724; border-left: 4px solid #28a745;
        }
        .error { background: #f8d7da; color: #721c24; border-left-color: #dc3545; }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            background: white; border-radius: 8px; overflow: hidden;
        }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status-badge {
            padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;
        }
        .status-yes { background: #d4edda; color: #155724; }
        .status-no { background: #f8d7da; color: #721c24; }
        .alert {
            padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #dc3545;
            background: #f8d7da; color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Email Logger System</h1>
        
        <div class="alert">
            <h4>⚠️ Email Delivery Issue Detected</h4>
            <p>Your server doesn't support direct email sending (neither SMTP nor PHP mail() function). This system generates credentials and logs them for manual email sending.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, '❌') ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card coaches">
                <div class="stat-number"><?= count($coaches_needing_credentials) ?></div>
                <div class="stat-label">Approved Coaches</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?= $queue_stats['pending'] ?></div>
                <div class="stat-label">Pending Emails</div>
            </div>
            <div class="stat-card failed">
                <div class="stat-number"><?= $queue_stats['failed'] ?></div>
                <div class="stat-label">Failed Emails</div>
            </div>
        </div>
        
        <!-- Actions -->
        <div style="text-align: center; margin: 30px 0;">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="generate_credentials">
                <button type="submit" class="btn btn-success">🔑 Generate Credentials & Log Emails</button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="mark_emails_sent">
                <button type="submit" class="btn btn-warning">✅ Mark Queue Emails as Sent</button>
            </form>
        </div>
        
        <!-- Log Files -->
        <?php if (!empty($log_files)): ?>
        <h3>📁 Generated Email Log Files</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($log_files as $file): ?>
                <tr>
                    <td><?= htmlspecialchars($file['name']) ?></td>
                    <td><?= number_format($file['size']) ?> bytes</td>
                    <td><?= $file['modified'] ?></td>
                    <td><a href="view_email_log.php?file=<?= urlencode($file['name']) ?>&access=viewlog2025" class="btn" style="text-decoration: none;">👁️ View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Coaches List -->
        <h3>👥 Approved Coaches</h3>
        <?php if (count($coaches_needing_credentials) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Has Password</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coaches_needing_credentials as $coach): ?>
                <tr>
                    <td><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></td>
                    <td><?= htmlspecialchars($coach['email']) ?></td>
                    <td><span class="status-badge status-yes"><?= htmlspecialchars($coach['status']) ?></span></td>
                    <td><span class="status-badge status-<?= $coach['has_password'] === 'Yes' ? 'yes' : 'no' ?>"><?= $coach['has_password'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No approved coaches found.</p>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div style="margin-top: 40px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
            <h4>📋 How to Use This System</h4>
            <ol>
                <li><strong>Generate Credentials:</strong> Click the button to create passwords and log email content</li>
                <li><strong>Download Log Files:</strong> View the generated email content from the log files</li>
                <li><strong>Send Manually:</strong> Copy email content and send via your personal email client</li>
                <li><strong>Mark as Sent:</strong> Update the queue to reflect that emails were sent manually</li>
            </ol>
            
            <h4>💡 Alternative Solutions</h4>
            <ul>
                <li>Contact your hosting provider to enable email sending</li>
                <li>Set up a third-party email service (SendGrid, Mailgun, etc.)</li>
                <li>Use a different hosting provider with email support</li>
            </ul>
        </div>
    </div>
</body>
</html>
