<?php
// minimal_email.php - Minimal working email system
if (!isset($_GET['access']) || $_GET['access'] !== 'minimal2025') {
    die('Access denied - use ?access=minimal2025');
}

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/db.php';
    
    $db = db();
    $message = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'test_connection') {
            $message = "Database connection: OK<br>";
            $message .= "Environment loaded: " . (isset($_ENV['SMTP_HOST']) ? 'YES' : 'NO') . "<br>";
            $message .= "PHPMailer available: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'YES' : 'NO') . "<br>";
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
                
                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup Test');
                $mail->addAddress('justusmunyoki@gmail.com');
                $mail->Subject = 'Test Email - ' . date('Y-m-d H:i:s');
                $mail->Body = 'This is a test email from the minimal email system.';
                
                $mail->send();
                $message = "✅ Test email sent successfully!";
                
            } catch (Exception $e) {
                $message = "❌ Email failed: " . $e->getMessage();
            }
        }
        
        if ($action === 'get_coaches') {
            try {
                $coaches = $db->query("
                    SELECT cr.id, u.first_name, u.last_name, u.email, u.username 
                    FROM coach_registrations cr 
                    JOIN users u ON cr.user_id = u.id 
                    WHERE cr.status = 'approved' AND u.role = 'coach'
                    LIMIT 10
                ");
                
                $message = "Found " . count($coaches) . " coaches:<br>";
                foreach ($coaches as $coach) {
                    $message .= "- {$coach['first_name']} {$coach['last_name']} ({$coach['email']})<br>";
                }
                
            } catch (Exception $e) {
                $message = "❌ Database error: " . $e->getMessage();
            }
        }
    }
    
} catch (Exception $e) {
    $message = "❌ System error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Minimal Email System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .btn { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .message { background: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <h1>🔧 Minimal Email System</h1>
    
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    
    <h3>System Tests</h3>
    
    <form method="POST" style="display: inline;">
        <input type="hidden" name="action" value="test_connection">
        <button type="submit" class="btn">Test Connection</button>
    </form>
    
    <form method="POST" style="display: inline;">
        <input type="hidden" name="action" value="get_coaches">
        <button type="submit" class="btn">Get Coaches</button>
    </form>
    
    <form method="POST" style="display: inline;">
        <input type="hidden" name="action" value="send_test_email">
        <button type="submit" class="btn">Send Test Email</button>
    </form>
    
    <hr>
    
    <h3>Environment Info</h3>
    <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
    <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
    <p><strong>Server:</strong> <?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></p>
    
</body>
</html>
