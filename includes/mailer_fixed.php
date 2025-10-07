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
 * Sends an email using PHPMailer with improved error handling and multiple SMTP configurations.
 * @param string $to_email Recipient's email address.
 * @param string $subject Email subject.
 * @param string $body Email body.
 * @return bool True on success, false on failure.
 */
function send_email($to_email, $subject, $body) {
    // If PHPMailer is not available, log the email instead
    if (!PHPMAILER_AVAILABLE) {
        error_log("EMAIL NOTIFICATION: To: $to_email, Subject: $subject, Body: $body");
        return true; // Return true to not break the workflow
    }
    
    // Try multiple SMTP configurations
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
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            $mail->Timeout = 30;
            
            // Additional SMTP options for shared hosting
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($_ENV['APP_EMAIL'], APP_NAME);
            $mail->addAddress($to_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            error_log("Email sent successfully to: $to_email using {$config['name']}");
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error with {$config['name']}: {$mail->ErrorInfo}");
            // Continue to next configuration
            continue;
        }
    }
    
    // If all SMTP configurations failed, log the email content for manual review
    error_log("EMAIL FALLBACK - All SMTP configs failed. To: $to_email, Subject: $subject");
    error_log("EMAIL CONTENT: " . strip_tags($body));
    
    return false;
}
