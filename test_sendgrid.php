<?php
// test_sendgrid.php - Test SendGrid email service
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['test']) || $_GET['test'] !== 'sendgrid2025') {
    die('Access denied');
}

require_once __DIR__ . '/config/config.php';

echo "<h2>SendGrid Setup and Test</h2>";

echo "<h3>1. SendGrid Setup Instructions</h3>";
echo "<ol>";
echo "<li><strong>Sign up for SendGrid:</strong><br>";
echo "   • Go to <a href='https://sendgrid.com' target='_blank'>sendgrid.com</a><br>";
echo "   • Create a free account (100 emails/day free)<br>";
echo "   • Verify your email address</li>";
echo "<li><strong>Create API Key:</strong><br>";
echo "   • Go to Settings > API Keys<br>";
echo "   • Click 'Create API Key'<br>";
echo "   • Choose 'Full Access' or 'Mail Send' permissions<br>";
echo "   • Copy the API key (starts with 'SG.')</li>";
echo "<li><strong>Verify Sender:</strong><br>";
echo "   • Go to Settings > Sender Authentication<br>";
echo "   • Add your email: governorwavinyacup@gmail.com<br>";
echo "   • Verify the sender email</li>";
echo "</ol>";

echo "<h3>2. Update Your .env File</h3>";
echo "<p>Add these lines to your .env file:</p>";
echo "<pre>";
echo "# SendGrid Configuration (Primary)
SENDGRID_API_KEY=SG.your_api_key_here
SENDGRID_FROM_EMAIL=governorwavinyacup@gmail.com
SENDGRID_FROM_NAME=Wavinyacup System

# Keep Gmail as backup
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=governorwavinyacup@gmail.com
SMTP_PASSWORD=\"icxk dzdc eglt btye\"
SMTP_ENCRYPTION=tls";
echo "</pre>";

echo "<h3>3. Install SendGrid Package</h3>";
echo "<p>Upload these files to your server:</p>";
echo "<ul>";
echo "<li><code>includes/sendgrid_mailer.php</code> (already created)</li>";
echo "<li>Install SendGrid via Composer or download manually</li>";
echo "</ul>";

echo "<h3>4. Current Environment Check</h3>";
if (isset($_ENV['SENDGRID_API_KEY'])) {
    echo "✅ SENDGRID_API_KEY: " . (strlen($_ENV['SENDGRID_API_KEY']) > 10 ? '[' . strlen($_ENV['SENDGRID_API_KEY']) . ' characters]' : 'TOO SHORT') . "<br>";
} else {
    echo "❌ SENDGRID_API_KEY: NOT SET<br>";
}

if (isset($_ENV['SENDGRID_FROM_EMAIL'])) {
    echo "✅ SENDGRID_FROM_EMAIL: " . $_ENV['SENDGRID_FROM_EMAIL'] . "<br>";
} else {
    echo "❌ SENDGRID_FROM_EMAIL: NOT SET<br>";
}

echo "<h3>5. Test SendGrid (After Setup)</h3>";
if (isset($_ENV['SENDGRID_API_KEY']) && !empty($_ENV['SENDGRID_API_KEY'])) {
    echo "<p><a href='test_sendgrid.php?test=sendgrid2025&send=true' class='btn'>Send Test Email</a></p>";
    
    if (isset($_GET['send']) && $_GET['send'] === 'true') {
        echo "<h4>Sending Test Email...</h4>";
        
        // Test SendGrid
        if (file_exists(__DIR__ . '/includes/sendgrid_mailer.php')) {
            require_once __DIR__ . '/includes/sendgrid_mailer.php';
            
            $test_email = $_ENV['SENDGRID_FROM_EMAIL'];
            $subject = 'SendGrid Test - ' . date('Y-m-d H:i:s');
            $body = '<h2>SendGrid Test Email</h2><p>This email was sent successfully via SendGrid!</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';
            
            if (send_email($test_email, $subject, $body)) {
                echo "✅ <strong>SUCCESS!</strong> Test email sent via SendGrid<br>";
                echo "Check your inbox: {$test_email}<br>";
            } else {
                echo "❌ Failed to send test email<br>";
            }
        } else {
            echo "❌ SendGrid mailer file not found<br>";
        }
    }
} else {
    echo "<p>⚠️ Configure SendGrid API key first</p>";
}

echo "<h3>6. Why SendGrid is Better</h3>";
echo "<ul>";
echo "<li>✅ Works reliably on shared hosting</li>";
echo "<li>✅ No IP blocking issues</li>";
echo "<li>✅ Better delivery rates</li>";
echo "<li>✅ 100 emails/day free tier</li>";
echo "<li>✅ Easy setup and management</li>";
echo "</ul>";

echo "<h3>7. Next Steps</h3>";
echo "<ol>";
echo "<li>Sign up for SendGrid (free)</li>";
echo "<li>Get your API key</li>";
echo "<li>Update your .env file</li>";
echo "<li>Test the email sending</li>";
echo "<li>Replace your current mailer</li>";
echo "</ol>";
?>
