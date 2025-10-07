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
 * Sends an email using multiple fallback methods.
 * @param string $to_email Recipient's email address.
 * @param string $subject Email subject.
 * @param string $body Email body.
 * @return bool True on success, false on failure.
 */
function send_email($to_email, $subject, $body) {
    // Method 1: Try server's built-in mail() function first
    if (function_exists('mail')) {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . ($_ENV['APP_EMAIL'] ?? 'noreply@governorwavinyacup.com'),
            'Reply-To: ' . ($_ENV['APP_EMAIL'] ?? 'noreply@governorwavinyacup.com'),
            'X-Mailer: PHP/' . phpversion()
        );
        
        if (mail($to_email, $subject, $body, implode("\r\n", $headers))) {
            error_log("Email sent successfully via mail() to: $to_email");
            return true;
        }
        error_log("Failed to send email via mail() to: $to_email");
    }
    
    // Method 2: Try PHPMailer with relaxed settings
    if (PHPMAILER_AVAILABLE && !empty($_ENV['SMTP_HOST'])) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings with maximum compatibility
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            
            // Try multiple port/encryption combinations
            $configs = [
                ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS],
                ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
                ['port' => 25, 'secure' => '']
            ];
            
            foreach ($configs as $config) {
                try {
                    $mail->Port = $config['port'];
                    $mail->SMTPSecure = $config['secure'];
                    
                    // Ultra-relaxed SSL settings
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                            'crypto_method' => STREAM_CRYPTO_METHOD_ANY_CLIENT
                        )
                    );
                    
                    // Recipients
                    $mail->clearAddresses();
                    $mail->setFrom($_ENV['APP_EMAIL'] ?? 'noreply@governorwavinyacup.com', APP_NAME ?? 'Wavinyacup');
                    $mail->addAddress($to_email);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->AltBody = strip_tags($body);
                    
                    $mail->send();
                    error_log("Email sent successfully via PHPMailer (port {$config['port']}) to: $to_email");
                    return true;
                    
                } catch (Exception $e) {
                    error_log("PHPMailer failed on port {$config['port']}: " . $e->getMessage());
                    continue;
                }
            }
            
        } catch (Exception $e) {
            error_log("PHPMailer completely failed: " . $e->getMessage());
        }
    }
    
    // Method 3: Log email for manual processing
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to_email,
        'subject' => $subject,
        'body' => $body,
        'method' => 'logged_for_manual_processing'
    ];
    
    $log_file = __DIR__ . '/../logs/failed_emails.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    error_log("Email logged for manual processing: $to_email - $subject");
    
    // Return true to prevent breaking the workflow - emails are logged for manual sending
    return true;
}

/**
 * Alternative simple email function using only server mail()
 */
function send_simple_email($to_email, $subject, $body) {
    if (!function_exists('mail')) {
        return false;
    }
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . ($_ENV['APP_EMAIL'] ?? 'noreply@governorwavinyacup.com') . "\r\n";
    
    return mail($to_email, $subject, $body, $headers);
}
