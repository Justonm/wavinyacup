<?php
// test_server_mail.php - Test server's built-in mail function
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'servermail2025') {
    die('Access denied - use ?access=servermail2025');
}

$message = '';
$mail_available = function_exists('mail');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_server_mail') {
        $test_email = $_POST['test_email'] ?? '';
        if ($test_email && $mail_available) {
            $subject = "Server Mail Test - " . date('Y-m-d H:i:s');
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Server Mail Function Test</h2>
                <p>This email was sent using PHP's built-in mail() function.</p>
                <p><strong>Sent at:</strong> " . date('Y-m-d H:i:s T') . "</p>
                <p><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</p>
                <p><strong>PHP Version:</strong> " . phpversion() . "</p>
                <p style='color: #27ae60;'>If you receive this, server mail is working!</p>
            </body>
            </html>";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: noreply@governorwavinyacup.com\r\n";
            $headers .= "Reply-To: noreply@governorwavinyacup.com\r\n";
            
            if (mail($test_email, $subject, $body, $headers)) {
                $message = "✅ Server mail sent successfully to {$test_email}!";
            } else {
                $message = "❌ Server mail failed to send to {$test_email}";
            }
        } elseif (!$mail_available) {
            $message = "❌ PHP mail() function is not available on this server";
        }
    }
}

// Check server capabilities
$server_info = [
    'mail_function' => function_exists('mail'),
    'sendmail_path' => ini_get('sendmail_path'),
    'smtp' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'php_version' => phpversion()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Server Mail Function</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f8f9fa; min-height: 100vh;
        }
        .container {
            max-width: 800px; margin: 0 auto; background: white; padding: 30px;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input[type="email"] {
            width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 6px;
            font-size: 16px; transition: border-color 0.3s ease;
        }
        input[type="email"]:focus { border-color: #007bff; outline: none; }
        .btn { 
            padding: 12px 24px; margin: 8px; background: #007bff; color: white; border: none;
            cursor: pointer; border-radius: 6px; font-size: 14px; transition: all 0.3s ease;
        }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .message { 
            padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 14px;
        }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info-box {
            background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px;
            padding: 20px; margin: 20px 0;
        }
        .config-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid #e9ecef;
        }
        .config-item:last-child { border-bottom: none; }
        .status-good { color: #28a745; font-weight: 600; }
        .status-bad { color: #dc3545; font-weight: 600; }
        .status-neutral { color: #6c757d; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📬 Test Server Mail Function</h1>
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, '✅') ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Server Capabilities -->
        <div class="info-box">
            <h3>🖥️ Server Mail Capabilities</h3>
            <div class="config-item">
                <span>PHP mail() function:</span>
                <span class="<?= $server_info['mail_function'] ? 'status-good' : 'status-bad' ?>">
                    <?= $server_info['mail_function'] ? 'Available ✓' : 'Not Available ✗' ?>
                </span>
            </div>
            <div class="config-item">
                <span>Sendmail Path:</span>
                <span class="<?= $server_info['sendmail_path'] ? 'status-good' : 'status-neutral' ?>">
                    <?= $server_info['sendmail_path'] ?: 'Default' ?>
                </span>
            </div>
            <div class="config-item">
                <span>SMTP Server:</span>
                <span class="<?= $server_info['smtp'] ? 'status-good' : 'status-neutral' ?>">
                    <?= $server_info['smtp'] ?: 'Default/Localhost' ?>
                </span>
            </div>
            <div class="config-item">
                <span>SMTP Port:</span>
                <span class="status-neutral"><?= $server_info['smtp_port'] ?: '25 (default)' ?></span>
            </div>
            <div class="config-item">
                <span>Server Name:</span>
                <span class="status-good"><?= $server_info['server_name'] ?></span>
            </div>
            <div class="config-item">
                <span>PHP Version:</span>
                <span class="status-good"><?= $server_info['php_version'] ?></span>
            </div>
        </div>
        
        <?php if ($mail_available): ?>
        <!-- Test Email Form -->
        <form method="POST">
            <div class="form-group">
                <label for="test_email">Test Email Address:</label>
                <input type="email" id="test_email" name="test_email" required 
                       placeholder="Enter email address to test server mail" 
                       value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>">
            </div>
            
            <div style="text-align: center;">
                <input type="hidden" name="action" value="test_server_mail">
                <button type="submit" class="btn btn-success">📧 Send Test Email (Server Mail)</button>
            </div>
        </form>
        <?php else: ?>
        <div class="message error">
            ❌ PHP mail() function is not available on this server. Server-based email sending is not possible.
        </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div class="info-box" style="margin-top: 30px;">
            <h3>📝 About Server Mail</h3>
            <p>This test uses PHP's built-in <code>mail()</code> function, which relies on the server's local mail configuration.</p>
            
            <h4>✅ Advantages:</h4>
            <ul>
                <li>No external SMTP authentication required</li>
                <li>Works with server's default mail setup</li>
                <li>Bypasses Gmail/external SMTP issues</li>
            </ul>
            
            <h4>⚠️ Considerations:</h4>
            <ul>
                <li>May be blocked by spam filters</li>
                <li>Delivery rates may be lower than authenticated SMTP</li>
                <li>Depends on server mail configuration</li>
            </ul>
            
            <h4>🔧 If this works:</h4>
            <p>We can update the bulk email system to use server mail instead of Gmail SMTP.</p>
        </div>
    </div>
</body>
</html>
