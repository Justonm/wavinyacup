<?php
// test_fixed_email.php - Test the fixed email system
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'testfix2025') {
    die('Access denied - use ?access=testfix2025');
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

// Include the fixed mailer
require_once dirname(__DIR__) . '/includes/mailer.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_email') {
        $test_email = $_POST['test_email'] ?? '';
        if ($test_email) {
            $subject = "Test Email from Fixed System - " . date('Y-m-d H:i:s');
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Email System Test</h2>
                <p>This is a test email from the fixed email system.</p>
                <p><strong>Sent at:</strong> " . date('Y-m-d H:i:s T') . "</p>
                <p><strong>SMTP Settings:</strong></p>
                <ul>
                    <li>Host: " . ($_ENV['SMTP_HOST'] ?? 'Not set') . "</li>
                    <li>Port: 587 (STARTTLS)</li>
                    <li>Username: " . ($_ENV['SMTP_USERNAME'] ?? 'Not set') . "</li>
                </ul>
                <p style='color: #27ae60;'>If you receive this email, the system is working correctly!</p>
            </body>
            </html>";
            
            if (send_email($test_email, $subject, $body)) {
                $message = "✅ Test email sent successfully to {$test_email}!";
            } else {
                $message = "❌ Failed to send test email to {$test_email}";
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
    <title>Test Fixed Email System</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test Fixed Email System</h1>
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, '✅') ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Configuration Status -->
        <div class="info-box">
            <h3>📋 Current Configuration</h3>
            <div class="config-item">
                <span>SMTP Host:</span>
                <span class="<?= !empty($_ENV['SMTP_HOST']) ? 'status-good' : 'status-bad' ?>">
                    <?= $_ENV['SMTP_HOST'] ?? 'Not configured' ?>
                </span>
            </div>
            <div class="config-item">
                <span>SMTP Port:</span>
                <span class="status-good">587 (STARTTLS)</span>
            </div>
            <div class="config-item">
                <span>SMTP Username:</span>
                <span class="<?= !empty($_ENV['SMTP_USERNAME']) ? 'status-good' : 'status-bad' ?>">
                    <?= $_ENV['SMTP_USERNAME'] ?? 'Not configured' ?>
                </span>
            </div>
            <div class="config-item">
                <span>SMTP Password:</span>
                <span class="<?= !empty($_ENV['SMTP_PASSWORD']) ? 'status-good' : 'status-bad' ?>">
                    <?= !empty($_ENV['SMTP_PASSWORD']) ? 'Configured ✓' : 'Not configured' ?>
                </span>
            </div>
            <div class="config-item">
                <span>SSL Verification:</span>
                <span class="status-good">Disabled (for shared hosting)</span>
            </div>
        </div>
        
        <!-- Test Email Form -->
        <form method="POST">
            <div class="form-group">
                <label for="test_email">Test Email Address:</label>
                <input type="email" id="test_email" name="test_email" required 
                       placeholder="Enter email address to test" 
                       value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>">
            </div>
            
            <div style="text-align: center;">
                <input type="hidden" name="action" value="test_email">
                <button type="submit" class="btn btn-success">📧 Send Test Email</button>
            </div>
        </form>
        
        <!-- Instructions -->
        <div class="info-box" style="margin-top: 30px;">
            <h3>📝 What This Test Does</h3>
            <ul>
                <li>Uses the fixed mailer.php with relaxed SSL settings</li>
                <li>Forces STARTTLS on port 587 (more compatible than SSL on 465)</li>
                <li>Disables SSL certificate verification for shared hosting</li>
                <li>Sends a formatted HTML test email</li>
            </ul>
            
            <h4>🔍 If the test fails:</h4>
            <ul>
                <li>Check that your Gmail app password is correct</li>
                <li>Verify 2-factor authentication is enabled on your Gmail account</li>
                <li>Ensure "Less secure app access" is disabled (use app passwords instead)</li>
                <li>Try generating a new app password</li>
            </ul>
        </div>
    </div>
</body>
</html>
