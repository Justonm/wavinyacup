<?php
// standalone_email.php - Completely standalone email system
if (!isset($_GET['access']) || $_GET['access'] !== 'standalone2025') {
    die('Access denied - use ?access=standalone2025');
}

// Load environment variables manually
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }
    }
}

$message = '';
$coaches = [];

// Database connection
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'wavinyacup';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_coaches') {
        try {
            $stmt = $pdo->query("
                SELECT cr.id, u.first_name, u.last_name, u.email, u.username 
                FROM coach_registrations cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.status = 'approved' AND u.role = 'coach'
                ORDER BY cr.created_at DESC
            ");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $message = "✅ Found " . count($coaches) . " approved coaches";
        } catch (Exception $e) {
            $message = "❌ Database error: " . $e->getMessage();
        }
    }
    
    if ($action === 'send_test_email') {
        // Load PHPMailer
        $autoload_file = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload_file)) {
            $message = "❌ PHPMailer not found. Composer autoload missing.";
        } else {
            try {
                require_once $autoload_file;
                
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception;
                
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
                $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
                
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup Test');
                $mail->addAddress('justusmunyoki@gmail.com');
                $mail->Subject = 'Standalone Test - ' . date('Y-m-d H:i:s');
                $mail->Body = '<h2>Standalone Email Test</h2><p>This email was sent from the standalone system at ' . date('Y-m-d H:i:s') . '</p>';
                $mail->isHTML(true);
                
                $mail->send();
                $message = "✅ Test email sent successfully!";
                
            } catch (Exception $e) {
                $message = "❌ Email failed: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'send_bulk_credentials') {
        $autoload_file = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload_file)) {
            $message = "❌ PHPMailer not found. Composer autoload missing.";
        } else {
            try {
                require_once $autoload_file;
                
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception;
                
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
                    
                    // Create email content
                    $email_subject = "Wavinyacup - Your Login Credentials";
                    $email_body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>
                                Welcome to Wavinyacup Coach Portal
                            </h2>
                            
                            <p>Dear <strong>{$coach['first_name']} {$coach['last_name']}</strong>,</p>
                            
                            <p>Your login credentials for the Wavinyacup coaching system have been generated:</p>
                            
                            <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 5px solid #007bff;'>
                                <h3 style='margin-top: 0; color: #495057;'>🔐 Login Details</h3>
                                <p style='margin: 10px 0;'><strong>Website:</strong> <a href='https://governorwavinyacup.com/wavinyacup/coach/dashboard.php' style='color: #007bff; text-decoration: none;'>Coach Dashboard</a></p>
                                <p style='margin: 10px 0;'><strong>Username:</strong> <span style='background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;'>{$coach['username']}</span></p>
                                <p style='margin: 10px 0;'><strong>Password:</strong> <span style='background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;'>{$password}</span></p>
                            </div>
                            
                            <div style='background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                                <p style='margin: 0;'><strong>⚠️ Important:</strong> Please keep these credentials secure and consider changing your password after first login.</p>
                            </div>
                            
                            <p>If you have any questions or need assistance, please contact the administration team.</p>
                            
                            <p style='margin-top: 30px;'>Best regards,<br>
                            <strong>Wavinyacup Administration Team</strong></p>
                            
                            <hr style='margin: 40px 0; border: none; border-top: 1px solid #dee2e6;'>
                            <p style='font-size: 12px; color: #6c757d; text-align: center;'>
                                This is an automated message from the Wavinyacup system.<br>
                                Generated on " . date('F j, Y \a\t g:i A') . "
                            </p>
                        </div>
                    </body>
                    </html>";
                    
                    // Send email
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
                        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
                        
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );
                        
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
                
                $message = "🎉 Bulk email complete! Sent: {$sent_count}, Failed: {$failed_count}";
                
            } catch (Exception $e) {
                $message = "❌ Bulk email error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Standalone Email System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
        }
        .btn { 
            padding: 14px 28px; 
            margin: 8px; 
            background: #3498db; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }
        .btn:hover { 
            background: #2980b9; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .message { 
            padding: 20px; 
            margin: 25px 0; 
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
        }
        .message:contains('✅') {
            background: #d4edda; 
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .message:contains('❌') {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        .coaches-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .coaches-table th, .coaches-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .coaches-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .coaches-table tr:hover {
            background: #f8f9fa;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .actions {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .system-info {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        .system-info h3 {
            margin-top: 0;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Standalone Email System</h1>
        
        <?php if ($message): ?>
            <div class="message">
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
                <div class="stat-number"><?= isset($_ENV['SMTP_HOST']) ? '✓' : '✗' ?></div>
                <div class="stat-label">SMTP Config</div>
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
        
        <div class="system-info">
            <h3>ℹ️ System Information</h3>
            <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
            <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s T') ?></p>
            <p><strong>SMTP Host:</strong> <?= $_ENV['SMTP_HOST'] ?? 'Not configured' ?></p>
            <p><strong>Database:</strong> Connected ✅</p>
            <p><strong>Composer Autoload:</strong> <?= file_exists(__DIR__ . '/../vendor/autoload.php') ? 'Available ✅' : 'Missing ❌' ?></p>
        </div>
    </div>
</body>
</html>
