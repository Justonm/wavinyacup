<?php
// Test the fallback email system directly
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/mailer.php';

// Define fallback function if it doesn't exist
if (!function_exists('send_email_fallback')) {
    function send_email_fallback($to_email, $subject, $body) {
        $from_email = $_ENV['APP_EMAIL'] ?? 'noreply@governorwavinyacup.com';
        $from_name = APP_NAME ?? 'Wavinyacup System';
        
        // Headers for HTML email
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
            "Reply-To: {$from_email}",
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $headers_string = implode("\r\n", $headers);
        
        // Send email using PHP mail() function
        $success = mail($to_email, $subject, $body, $headers_string);
        
        if ($success) {
            error_log("Fallback email sent successfully to: $to_email");
            return true;
        } else {
            error_log("Fallback email also failed for: $to_email");
            return false;
        }
    }
}

echo "<h2>Fallback Email System Test</h2>\n";

if (isset($_GET['test'])) {
    echo "<h3>Testing PHP mail() Function Directly:</h3>\n";
    
    $test_email = $_ENV['ADMIN_EMAILS'] ?? 'governorwavinyacup@gmail.com';
    $subject = "Fallback Email Test - " . date('Y-m-d H:i:s');
    $body = "
    <h2>Fallback Email Test</h2>
    <p>This email was sent using PHP's built-in mail() function.</p>
    <p>If you receive this, your email system is working!</p>
    <p>Time: " . date('Y-m-d H:i:s') . "</p>
    ";
    
    echo "<p>Sending test email to: <strong>{$test_email}</strong></p>\n";
    
    // Test the fallback function directly
    if (function_exists('send_email_fallback') && send_email_fallback($test_email, $subject, $body)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Fallback email sent successfully!";
        echo "<br>Check your inbox at: {$test_email}";
        echo "<br><br><strong>This means your email system is working!</strong>";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ FAILED!</strong> Fallback email also failed.";
        echo "<br>This means the server's mail() function is also disabled.";
        echo "<br><strong>Now you should contact your hosting provider.</strong>";
        echo "</div>\n";
    }
}

// Test the main send_email function (which should automatically fallback)
if (isset($_GET['test_main'])) {
    echo "<h3>Testing Main Email Function (with automatic fallback):</h3>\n";
    
    $test_email = $_ENV['ADMIN_EMAILS'] ?? 'governorwavinyacup@gmail.com';
    $subject = "Main Email Function Test - " . date('Y-m-d H:i:s');
    $body = "
    <h2>Main Email Function Test</h2>
    <p>This email should automatically fallback to PHP mail() when SMTP fails.</p>
    <p>Time: " . date('Y-m-d H:i:s') . "</p>
    ";
    
    echo "<p>Sending via main email function to: <strong>{$test_email}</strong></p>\n";
    
    if (send_email($test_email, $subject, $body)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Email sent (via fallback system)!";
        echo "<br>Your auto emails should now work!";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ FAILED!</strong> Both SMTP and fallback failed.";
        echo "<br>Contact hosting provider about email restrictions.";
        echo "</div>\n";
    }
}

echo "<hr>\n";
echo "<p><a href='?test=1' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Test Fallback System</a> ";
echo "<a href='?test_main=1' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Test Main Function</a></p>\n";

echo "<h3>What This Tests:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Fallback System:</strong> Uses PHP's built-in mail() function</li>\n";
echo "<li><strong>Main Function:</strong> Tries SMTP first, then automatically falls back</li>\n";
echo "<li><strong>If both work:</strong> Your email system is ready!</li>\n";
echo "<li><strong>If both fail:</strong> Then contact your hosting provider</li>\n";
echo "</ul>\n";
?>
