<?php
// sendgrid_mailer.php - SendGrid email implementation
require_once __DIR__ . '/../vendor/autoload.php';

use SendGrid\Mail\Mail;

function send_email_sendgrid($to_email, $subject, $body) {
    try {
        $email = new Mail();
        $email->setFrom($_ENV['SENDGRID_FROM_EMAIL'], $_ENV['SENDGRID_FROM_NAME'] ?? 'Wavinyacup System');
        $email->setSubject($subject);
        $email->addTo($to_email);
        $email->addContent("text/html", $body);

        $sendgrid = new \SendGrid($_ENV['SENDGRID_API_KEY']);
        $response = $sendgrid->send($email);

        if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
            error_log("Email sent successfully to {$to_email} via SendGrid");
            return true;
        } else {
            error_log("SendGrid error: " . $response->body());
            return false;
        }
    } catch (Exception $e) {
        error_log("SendGrid exception: " . $e->getMessage());
        return false;
    }
}

// Fallback function that tries SendGrid first, then PHPMailer
function send_email($to_email, $subject, $body) {
    // Try SendGrid first if API key is configured
    if (!empty($_ENV['SENDGRID_API_KEY'])) {
        if (send_email_sendgrid($to_email, $subject, $body)) {
            return true;
        }
        error_log("SendGrid failed, trying PHPMailer fallback");
    }
    
    // Fallback to PHPMailer (existing Gmail setup)
    return send_email_phpmailer($to_email, $subject, $body);
}

function send_email_phpmailer($to_email, $subject, $body) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        
        // Relaxed SSL settings for shared hosting
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
            )
        );
        
        $mail->setFrom($_ENV['SMTP_USERNAME'], 'Wavinyacup System');
        $mail->addAddress($to_email);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(true);
        
        $mail->send();
        error_log("Email sent successfully to {$to_email} via PHPMailer");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
        
        // Log email content for debugging
        error_log("Failed email - To: {$to_email}, Subject: {$subject}");
        return false;
    }
}
?>
