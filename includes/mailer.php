<?php

// Check if PHPMailer is available via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    define('PHPMAILER_AVAILABLE', true);
} else {
    define('PHPMAILER_AVAILABLE', false);
}

function send_email($to_email, $subject, $body) {
    // If PHPMailer is not available, log the email instead
    if (!PHPMAILER_AVAILABLE) {
        error_log("EMAIL NOTIFICATION: To: $to_email, Subject: $subject, Body: $body");
        return true; // Return true to not break the workflow
    }
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        
        // Additional Gmail settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom(APP_EMAIL, APP_NAME);
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        error_log("Email sent successfully to: $to_email");
        return true;
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Gmail SMTP failed: {$mail->ErrorInfo}");
        
        // Log the email content for manual review
        error_log("EMAIL FALLBACK - To: $to_email, Subject: $subject");
        error_log("EMAIL CONTENT: " . strip_tags($body));
        
        // Return true to not break the workflow - emails are logged for manual sending
        return true;
    }
}
?>