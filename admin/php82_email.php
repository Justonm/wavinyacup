<?php
// php82_email.php - PHP 8.2 compatible email system
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'php82_2025') {
    die('Access denied - use ?access=php82_2025');
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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_coaches') {
        try {
            // First check what's in the tables
            $total_coaches = $pdo->query("SELECT COUNT(*) as count FROM coach_registrations")->fetch()['count'];
            $approved_coaches = $pdo->query("SELECT COUNT(*) as count FROM coach_registrations WHERE status = 'approved'")->fetch()['count'];
            $users_with_coach_role = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'coach'")->fetch()['count'];
            
            // Try different query approaches
            $stmt = $pdo->query("
                SELECT cr.id, u.first_name, u.last_name, u.email, u.username, cr.status, u.role
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.status = 'approved'
                ORDER BY cr.created_at DESC
            ");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $message = "Database stats: Total coaches: {$total_coaches}, Approved: {$approved_coaches}, Users with coach role: {$users_with_coach_role}, Query result: " . count($coaches);
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
    
    if ($action === 'send_test_email') {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            $message = "PHPMailer not found - Composer autoload missing";
        } else {
            try {
                require_once $autoload;
                
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
                $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 587);
                $mail->SMTPDebug = 0; // Disable debug output
                
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup Test');
                $mail->addAddress('justusmunyoki@gmail.com');
                $mail->Subject = 'SMTP Test - ' . date('H:i:s');
                $mail->Body = '<h2>SMTP Connection Test</h2><p>Sent at: ' . date('Y-m-d H:i:s') . '</p><p>SMTP Host: ' . $_ENV['SMTP_HOST'] . '</p><p>Username: ' . $_ENV['SMTP_USERNAME'] . '</p>';
                $mail->isHTML(true);
                
                $mail->send();
                $message = "✅ Test email sent successfully to justusmunyoki@gmail.com!";
                
            } catch (Exception $e) {
                $message = "❌ SMTP Error: " . $e->getMessage() . " | Host: " . ($_ENV['SMTP_HOST'] ?? 'not set') . " | Username: " . ($_ENV['SMTP_USERNAME'] ?? 'not set');
            }
        }
    }
    
    if ($action === 'send_bulk_credentials') {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            $message = "PHPMailer not found - Composer autoload missing";
        } else {
            try {
                require_once $autoload;
                
                // Get all coaches
                $stmt = $pdo->query("
                    SELECT cr.id, cr.user_id, u.first_name, u.last_name, u.email, u.username 
                    FROM coach_registrations cr 
                    JOIN users u ON cr.user_id = u.id 
                    WHERE cr.status = 'approved' AND u.role = 'coach'
                ");
                $all_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $sent_count = 0;
                $failed_count = 0;
                
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
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Wavinyacup Login Credentials</title>
                    </head>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                            <h1 style='margin: 0; font-size: 28px;'>🏆 Wavinyacup</h1>
                            <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Coach Portal Access</p>
                        </div>
                        
                        <div style='background: white; padding: 30px; border: 1px solid #e9ecef; border-top: none; border-radius: 0 0 10px 10px;'>
                            <p style='font-size: 18px; margin-bottom: 20px;'>Dear <strong>{$coach['first_name']} {$coach['last_name']}</strong>,</p>
                            
                            <p>Your login credentials for the Wavinyacup coaching system have been generated. You can now access your coach dashboard to manage your team and players.</p>
                            
                            <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 5px solid #007bff;'>
                                <h3 style='margin-top: 0; color: #495057; font-size: 20px;'>🔐 Your Login Details</h3>
                                <table style='width: 100%; border-collapse: collapse;'>
                                    <tr>
                                        <td style='padding: 8px 0; font-weight: bold; width: 100px;'>Website:</td>
                                        <td style='padding: 8px 0;'><a href='https://governorwavinyacup.com/wavinyacup/coach/dashboard.php' style='color: #007bff; text-decoration: none;'>Coach Dashboard</a></td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px 0; font-weight: bold;'>Username:</td>
                                        <td style='padding: 8px 0;'><code style='background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 14px;'>{$coach['username']}</code></td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px 0; font-weight: bold;'>Password:</td>
                                        <td style='padding: 8px 0;'><code style='background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 14px;'>{$password}</code></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style='background: #fff3cd; padding: 20px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 25px 0;'>
                                <p style='margin: 0; font-weight: bold; color: #856404;'>⚠️ Important Security Notice</p>
                                <p style='margin: 10px 0 0 0; color: #856404;'>Please keep these credentials secure and consider changing your password after your first login for enhanced security.</p>
                            </div>
                            
                            <div style='background: #d4edda; padding: 20px; border-radius: 6px; border-left: 4px solid #28a745; margin: 25px 0;'>
                                <p style='margin: 0; font-weight: bold; color: #155724;'>📋 What You Can Do</p>
                                <ul style='margin: 10px 0 0 0; color: #155724; padding-left: 20px;'>
                                    <li>Manage your team roster</li>
                                    <li>Add and edit player information</li>
                                    <li>View tournament schedules</li>
                                    <li>Access team statistics</li>
                                </ul>
                            </div>
                            
                            <p style='margin-top: 30px;'>If you have any questions or need assistance accessing your account, please contact the administration team.</p>
                            
                            <p style='margin-top: 30px;'>Best regards,<br>
                            <strong>Wavinyacup Administration Team</strong></p>
                        </div>
                        
                        <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                            <p style='margin: 0;'>This is an automated message from the Wavinyacup system.</p>
                            <p style='margin: 5px 0 0 0;'>Generated on " . date('F j, Y \a\t g:i A') . "</p>
                        </div>
                    </body>
                    </html>";
                    
                    // Send email
                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = $_ENV['SMTP_HOST'];
                        $mail->SMTPAuth = true;
                        $mail->Username = $_ENV['SMTP_USERNAME'];
                        $mail->Password = $_ENV['SMTP_PASSWORD'];
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = (int)$_ENV['SMTP_PORT'];
                        
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ];
                        
                        $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
                        $mail->addAddress($coach['email']);
                        $mail->Subject = $email_subject;
                        $mail->Body = $email_body;
                        $mail->isHTML(true);
                        
                        $mail->send();
                        $sent_count++;
                        
                    } catch (Exception $e) {
                        $failed_count++;
                        error_log("Email failed for {$coach['email']}: " . $e->getMessage());
                    }
                }
                
                $message = "Bulk email complete! Sent: {$sent_count}, Failed: {$failed_count}";
                
            } catch (Exception $e) {
                $message = "Bulk email error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP 8.2 Email System</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 PHP 8.2 Email System</h1>
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, 'error') || str_contains($message, 'failed') ? 'error' : '' ?>">
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
                <div class="stat-number">8.2</div>
                <div class="stat-label">PHP Version</div>
            </div>
        </div>
        
        <div class="actions">
            <h3>📧 Email Actions</h3>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="get_coaches">
                <button type="submit" class="btn">📋 Get Coaches</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="send_test_email">
                <button type="submit" class="btn btn-success">✉️ Send Test Email</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="send_bulk_credentials">
                <button type="submit" class="btn btn-warning" onclick="return confirm('⚠️ This will generate NEW passwords for ALL coaches and send them via email. Continue?')">
                    📤 Send Bulk Credentials
                </button>
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
                    <th>Coach ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coaches as $coach): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></strong></td>
                    <td><?= htmlspecialchars($coach['email']) ?></td>
                    <td><code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($coach['username']) ?></code></td>
                    <td><?= htmlspecialchars($coach['id']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>
