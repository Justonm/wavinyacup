<?php
// email_diagnostic_advanced.php - Advanced email diagnostics and fixes
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

// Security check
if (!isset($_GET['diag']) || $_GET['diag'] !== 'advanced2025') {
    die('Access denied');
}

echo "<h2>Advanced Email Diagnostics</h2>";

$db = db();

// Test 1: Check if PHPMailer can be instantiated
echo "<h3>1. PHPMailer Instantiation Test</h3>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use PHPMailer\PHPMailer\SMTP;
    
    $mail = new PHPMailer(true);
    echo "✅ PHPMailer instantiated successfully<br>";
} catch (Exception $e) {
    echo "❌ PHPMailer instantiation failed: " . $e->getMessage() . "<br>";
    die("Cannot proceed without PHPMailer");
}

// Test 2: Raw SMTP connection test
echo "<h3>2. Raw SMTP Connection Test</h3>";
try {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'governorwavinyacup@gmail.com';
    $mail->Password = 'hnkp ubbw cvdd bqcm';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Test connection without sending
    if ($mail->smtpConnect()) {
        echo "✅ SMTP connection successful<br>";
        $mail->smtpClose();
    } else {
        echo "❌ SMTP connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ SMTP connection error: " . $e->getMessage() . "<br>";
}

// Test 3: Try alternative SMTP settings
echo "<h3>3. Testing Alternative SMTP Settings</h3>";

$smtp_configs = [
    [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS,
        'name' => 'Gmail TLS 587'
    ],
    [
        'host' => 'smtp.gmail.com', 
        'port' => 465,
        'encryption' => PHPMailer::ENCRYPTION_SMTPS,
        'name' => 'Gmail SSL 465'
    ]
];

foreach ($smtp_configs as $config) {
    echo "Testing {$config['name']}... ";
    
    try {
        $testMail = new PHPMailer(true);
        $testMail->SMTPDebug = SMTP::DEBUG_OFF;
        $testMail->isSMTP();
        $testMail->Host = $config['host'];
        $testMail->SMTPAuth = true;
        $testMail->Username = 'governorwavinyacup@gmail.com';
        $testMail->Password = 'hnkp ubbw cvdd bqcm';
        $testMail->SMTPSecure = $config['encryption'];
        $testMail->Port = $config['port'];
        $testMail->Timeout = 30;
        
        $testMail->setFrom('governorwavinyacup@gmail.com', 'Wavinya Cup');
        $testMail->addAddress('governorwavinyacup@gmail.com');
        $testMail->Subject = 'Test - ' . $config['name'] . ' - ' . date('H:i:s');
        $testMail->Body = 'This is a test email using ' . $config['name'];
        
        if ($testMail->send()) {
            echo "✅ SUCCESS<br>";
            echo "<strong>Working configuration found: {$config['name']}</strong><br>";
            
            // Update .env with working config
            echo "Update your .env file with:<br>";
            echo "SMTP_PORT={$config['port']}<br>";
            echo "SMTP_ENCRYPTION=" . ($config['encryption'] == PHPMailer::ENCRYPTION_STARTTLS ? 'tls' : 'ssl') . "<br>";
            break;
        } else {
            echo "❌ Failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Check server capabilities
echo "<h3>4. Server Capabilities</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "cURL: " . (extension_loaded('curl') ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ Enabled' : '❌ Disabled') . "<br>";

// Test 5: Alternative email method using PHP mail()
echo "<h3>5. Testing PHP mail() Function</h3>";
$to = 'governorwavinyacup@gmail.com';
$subject = 'Test PHP mail() - ' . date('Y-m-d H:i:s');
$message = 'This is a test using PHP mail() function';
$headers = 'From: governorwavinyacup@gmail.com' . "\r\n" .
           'Reply-To: governorwavinyacup@gmail.com' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "✅ PHP mail() function works<br>";
} else {
    echo "❌ PHP mail() function failed<br>";
}

// Test 6: Check email queue and suggest cleanup
echo "<h3>6. Email Queue Management</h3>";
$queue_stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM email_queue
");

echo "Queue Status: Total: {$queue_stats['total']}, Pending: {$queue_stats['pending']}, Sent: {$queue_stats['sent']}, Failed: {$queue_stats['failed']}<br>";

if ($queue_stats['failed'] > 0) {
    echo "<form method='post' style='margin: 10px 0;'>";
    echo "<button type='submit' name='clear_failed' style='background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px;'>Clear Failed Emails</button>";
    echo "</form>";
}

// Handle clear failed emails
if (isset($_POST['clear_failed'])) {
    $deleted = $db->query("DELETE FROM email_queue WHERE status = 'failed'")->rowCount();
    echo "✅ Deleted {$deleted} failed emails<br>";
}

echo "<h3>7. Recommendations</h3>";
echo "1. If any SMTP configuration worked above, update your .env file accordingly<br>";
echo "2. If PHP mail() worked, consider using it as fallback<br>";
echo "3. Contact your hosting provider about SMTP restrictions<br>";
echo "4. Consider using a transactional email service (SendGrid, Mailgun, etc.)<br>";
?>
