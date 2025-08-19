<?php
require_once 'config/config.php';
require_once 'includes/mailer.php';

echo "<h2>Email Test - " . APP_NAME . "</h2>";

// Test email configuration
echo "<h3>Configuration Check:</h3>";
echo "SMTP Host: " . SMTP_HOST . "<br>";
echo "SMTP Port: " . SMTP_PORT . "<br>";
echo "SMTP Username: " . SMTP_USERNAME . "<br>";
echo "App Email: " . APP_EMAIL . "<br>";
echo "PHPMailer Available: " . (PHPMAILER_AVAILABLE ? 'Yes' : 'No') . "<br><br>";

if (PHPMAILER_AVAILABLE) {
    echo "<h3>Sending Test Email...</h3>";
    
    $test_subject = "Test Email - " . APP_NAME;
    $test_body = "
        <h2>Email Test Successful!</h2>
        <p>This is a test email from the Wavinya Cup system.</p>
        <p>If you receive this email, your SMTP configuration is working correctly.</p>
        <p>Timestamp: " . date('Y-m-d H:i:s') . "</p>
        <p>Best regards,<br>" . APP_NAME . " System</p>
    ";
    
    $result = send_email(APP_EMAIL, $test_subject, $test_body);
    
    if ($result) {
        echo "<div style='color: green; font-weight: bold;'>✅ Test email sent successfully!</div>";
        echo "<p>Check your inbox at: " . APP_EMAIL . "</p>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Failed to send test email</div>";
        echo "<p>Please check your SMTP configuration and App Password.</p>";
    }
} else {
    echo "<div style='color: orange; font-weight: bold;'>⚠️ PHPMailer not available</div>";
    echo "<p>Emails will be logged instead of sent.</p>";
}

echo "<br><hr><br>";
echo "<h3>📧 Gmail Setup for New Accounts</h3>";
echo "<div style='background: #e8f5e8; padding: 20px; border-left: 4px solid #28a745; margin: 10px 0;'>";
echo "<h4>🔐 Step 1: Enable 2-Factor Authentication</h4>";
echo "<ol>";
echo "<li>Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>";
echo "<li>Click '2-Step Verification' → 'Get Started'</li>";
echo "<li>Add your phone number for SMS verification</li>";
echo "<li>Complete the setup process</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
echo "<h4>⏰ Step 2: Wait 24-48 Hours</h4>";
echo "<p>New Gmail accounts need time before App Passwords become available after enabling 2FA.</p>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 10px 0;'>";
echo "<h4>🔑 Step 3: Generate App Password (After waiting)</h4>";
echo "<ol>";
echo "<li>Return to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>";
echo "<li>Look for 'App passwords' section</li>";
echo "<li>Select 'Mail' → 'Other (custom name)' → 'Wavinya Cup'</li>";
echo "<li>Copy the 16-character password</li>";
echo "<li>Update .env: <code>SMTP_PASSWORD=your-16-char-app-password</code></li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #155724; margin: 10px 0;'>";
echo "<h4>🧪 Try Current Setup First</h4>";
echo "<p>Your current Gmail configuration might work temporarily with new accounts. Test it now!</p>";
echo "</div>";

echo "<h3>Current Status:</h3>";
echo "<p>The system will log all emails if SMTP fails, so the workflow won't break.</p>";
echo "<p>Check error logs for email content that needs to be sent manually.</p>";
?>
