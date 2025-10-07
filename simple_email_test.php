<?php
// simple_email_test.php - Simple email test without complex dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security check
if (!isset($_GET['test']) || $_GET['test'] !== 'simple2025') {
    die('Access denied');
}

echo "<h2>Simple Email Test</h2>";

// Test 1: Basic PHP info
echo "<h3>1. PHP Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "<br>";

// Test 2: Check if files exist
echo "<h3>2. File Check</h3>";
$files_to_check = [
    'config/config.php',
    'includes/db.php', 
    'includes/mailer.php',
    'vendor/autoload.php',
    '.env'
];

foreach ($files_to_check as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "{$file}: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
}

// Test 3: Try to load config safely
echo "<h3>3. Config Loading</h3>";
try {
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
        echo "✅ Config loaded successfully<br>";
        
        // Check environment variables
        echo "APP_EMAIL: " . ($_ENV['APP_EMAIL'] ?? 'NOT SET') . "<br>";
        echo "SMTP_HOST: " . ($_ENV['SMTP_HOST'] ?? 'NOT SET') . "<br>";
        echo "SMTP_PORT: " . ($_ENV['SMTP_PORT'] ?? 'NOT SET') . "<br>";
    } else {
        echo "❌ Config file missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Config loading failed: " . $e->getMessage() . "<br>";
}

// Test 4: Database connection
echo "<h3>4. Database Connection</h3>";
try {
    if (function_exists('db')) {
        $db = db();
        $result = $db->query("SELECT COUNT(*) as count FROM email_queue");
        if ($result) {
            $count = $result->fetch()['count'];
            echo "✅ Database connected. Email queue has {$count} emails<br>";
        }
    } else {
        echo "❌ db() function not available<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Simple direct test of PHP mail() function
echo "<h2>Direct PHP mail() Test</h2>\n";

$to = 'governorwavinyacup@gmail.com';
$subject = 'Direct PHP Mail Test - ' . date('Y-m-d H:i:s');
$message = '<h2>Direct PHP Mail Test</h2><p>This email was sent directly using PHP mail() function.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';

$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: Wavinyacup System <noreply@governorwavinyacup.com>',
    'Reply-To: noreply@governorwavinyacup.com',
    'X-Mailer: PHP/' . phpversion()
];

$headers_string = implode("\r\n", $headers);

echo "<p>Attempting to send email to: <strong>{$to}</strong></p>\n";
echo "<p>Subject: {$subject}</p>\n";

if (mail($to, $subject, $message, $headers_string)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✅ SUCCESS!</strong> Email sent successfully using PHP mail()!";
    echo "<br>Check your inbox at: {$to}";
    echo "<br><br><strong>Your server supports PHP mail() - email system will work!</strong>";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ FAILED!</strong> PHP mail() function failed.";
    echo "<br>Your hosting provider has disabled the mail() function.";
    echo "<br><strong>Contact your hosting provider to enable PHP mail().</strong>";
    echo "</div>\n";
}

echo "<h3>Next Steps:</h3>\n";
echo "<ul>\n";
echo "<li>If SUCCESS: Your email system will work - set up the cron job</li>\n";
echo "<li>If FAILED: Contact hosting provider about enabling PHP mail()</li>\n";
echo "</ul>\n";

// Test 6: Manual email queue processing
echo "<h3>6. Manual Queue Processing</h3>";
if (function_exists('db')) {
    try {
        $db = db();
        
        // Get one pending email
        $email = $db->query("SELECT * FROM email_queue WHERE status = 'pending' LIMIT 1")->fetch();
        
        if ($email) {
            echo "Found pending email to: " . htmlspecialchars($email['recipient']) . "<br>";
            
            // Try to send with PHP mail
            $sent = mail(
                $email['recipient'],
                $email['subject'],
                $email['body'],
                $headers
            );
            
            if ($sent) {
                $db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$email['id']]);
                echo "✅ Email sent and marked as sent<br>";
            } else {
                echo "❌ Failed to send email<br>";
            }
        } else {
            echo "No pending emails found<br>";
        }
    } catch (Exception $e) {
        echo "❌ Queue processing failed: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>7. Next Steps</h3>";
echo "1. If PHP mail() works, we can modify the system to use it instead of SMTP<br>";
echo "2. Check your hosting provider's email sending policies<br>";
echo "3. Consider using a transactional email service<br>";
?>
