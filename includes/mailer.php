<?php

// Ensure the main config file is loaded to have access to environment variables and constants.
require_once __DIR__ . '/../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if PHPMailer is available via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    define('PHPMAILER_AVAILABLE', true);
} else {
    define('PHPMAILER_AVAILABLE', false);
}

/**
 * Sends an email using PHPMailer with fallback to PHP mail().
 * @param string $to_email Recipient's email address.
 * @param string $subject Email subject.
 * @param string $body Email body.
 * @return bool True on success, false on failure.
 */
function send_email($to_email, $subject, $body) {
    if (!PHPMAILER_AVAILABLE) {
        error_log("PHPMailer is not available. Please install it via Composer.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Enable server-side debugging to the error log
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'error_log';

        // Server settings
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        // Recipients
        $mail->setFrom($_ENV['APP_EMAIL'], APP_NAME);
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        error_log("Email sent successfully to: $to_email");
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
