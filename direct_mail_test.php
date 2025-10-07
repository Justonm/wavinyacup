<?php
// Direct PHP mail() test - no dependencies
echo "<h2>Direct PHP Mail Test</h2>\n";

$to = 'governorwavinyacup@gmail.com';
$subject = 'Direct Mail Test - ' . date('Y-m-d H:i:s');
$message = '<h2>Direct Mail Test</h2><p>This email was sent directly using PHP mail() function.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';

$headers = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
$headers .= 'From: Wavinyacup System <noreply@governorwavinyacup.com>' . "\r\n";
$headers .= 'Reply-To: noreply@governorwavinyacup.com' . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion();

echo "<p>Sending to: <strong>{$to}</strong></p>\n";
echo "<p>Subject: {$subject}</p>\n";

if (mail($to, $subject, $message, $headers)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✅ SUCCESS!</strong> PHP mail() works!";
    echo "<br>Check your inbox at: {$to}";
    echo "<br><br><strong>Your email system can work - I'll fix the fallback function.</strong>";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ FAILED!</strong> PHP mail() is disabled.";
    echo "<br>Contact your hosting provider to enable PHP mail().";
    echo "</div>\n";
}
?>
