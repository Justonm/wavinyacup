<?php
// working_email.php - Working email system
if (!isset($_GET['access']) || $_GET['access'] !== 'work2025') {
    die('Access denied - use ?access=work2025');
}

require_once __DIR__ . '/../config/config.php';

$message = '';
$coaches = [];

// Get database connection
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASS']);
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
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;
            
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'];
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
            $mail->addAddress('justusmunyoki@gmail.com');
            $mail->Subject = 'Test Email - ' . date('Y-m-d H:i:s');
            $mail->Body = '<h2>Test Email</h2><p>This is a test from the working email system.</p>';
            $mail->isHTML(true);
            
            $mail->send();
            $message = "✅ Test email sent successfully to justusmunyoki@gmail.com!";
            
        } catch (Exception $e) {
            $message = "❌ Email failed: " . $e->getMessage();
        }
    }
    
    if ($action === 'send_bulk_credentials') {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
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
                
                // Create email
                $email_subject = "Wavinyacup - Your Login Credentials";
                $email_body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2 style='color: #2c3e50;'>Welcome to Wavinyacup Coach Portal</h2>
                    
                    <p>Dear {$coach['first_name']} {$coach['last_name']},</p>
                    
                    <p>Your login credentials for the Wavinyacup system have been generated:</p>
                    
                    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff;'>
                        <h3 style='margin-top: 0; color: #495057;'>Login Details</h3>
                        <p><strong>Website:</strong> <a href='https://governorwavinyacup.com/wavinyacup/coach/dashboard.php'>Coach Dashboard</a></p>
                        <p><strong>Username:</strong> <code style='background: #e9ecef; padding: 2px 6px; border-radius: 3px;'>{$coach['username']}</code></p>
                        <p><strong>Password:</strong> <code style='background: #e9ecef; padding: 2px 6px; border-radius: 3px;'>{$password}</code></p>
                    </div>
                    
                    <p><strong>Important:</strong> Please keep these credentials secure and change your password after first login.</p>
                    
                    <p>If you have any questions, please contact the administration team.</p>
                    
                    <p>Best regards,<br>
                    <strong>Wavinyacup Administration Team</strong></p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #dee2e6;'>
                    <p style='font-size: 12px; color: #6c757d;'>This is an automated message. Please do not reply to this email.</p>
                </body>
                </html>";
                
                // Send email
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $_ENV['SMTP_HOST'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['SMTP_USERNAME'];
                    $mail->Password = $_ENV['SMTP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $_ENV['SMTP_PORT'];
                    
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
            
            $message = "✅ Bulk email complete! Sent: {$sent_count}, Failed: {$failed_count}";
            
        } catch (Exception $e) {
            $message = "❌ Bulk email error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Working Email System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background: #f8f9fa; 
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn { 
            padding: 12px 24px; 
            margin: 10px; 
            background: #007bff; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 5px;
            font-size: 14px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .message { 
            background: #d4edda; 
            color: #155724;
            padding: 15px; 
            margin: 20px 0; 
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        .coaches-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .coaches-table th, .coaches-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .coaches-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            flex: 1;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Working Email System</h1>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '❌') !== false ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($coaches) ?></div>
                <div>Coaches Found</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= date('H:i') ?></div>
                <div>Current Time</div>
            </div>
        </div>
        
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
            <button type="submit" class="btn btn-warning" onclick="return confirm('Send credentials to ALL coaches? This will generate new passwords!')">
                📤 Send Bulk Credentials
            </button>
        </form>
        
        <?php if (count($coaches) > 0): ?>
        <h3>👥 Approved Coaches (<?= count($coaches) ?>)</h3>
        <table class="coaches-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coaches as $coach): ?>
                <tr>
                    <td><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></td>
                    <td><?= htmlspecialchars($coach['email']) ?></td>
                    <td><code><?= htmlspecialchars($coach['username']) ?></code></td>
                    <td><?= htmlspecialchars($coach['id']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <hr style="margin: 40px 0;">
        
        <h3>ℹ️ System Info</h3>
        <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
        <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
        <p><strong>SMTP Host:</strong> <?= $_ENV['SMTP_HOST'] ?? 'Not configured' ?></p>
        <p><strong>Database:</strong> Connected ✅</p>
        
    </div>
</body>
</html>
