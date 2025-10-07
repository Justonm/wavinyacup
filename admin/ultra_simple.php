<?php
// ultra_simple.php - Ultra minimal version
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['access']) || $_GET['access'] !== 'ultra2025') {
    echo 'Access denied - use ?access=ultra2025';
    exit;
}

echo "<h1>Ultra Simple Email System</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Check if .env exists
$env_file = __DIR__ . '/../.env';
echo "<p>.env file exists: " . (file_exists($env_file) ? 'YES' : 'NO') . "</p>";

// Check if vendor exists
$vendor_file = __DIR__ . '/../vendor/autoload.php';
echo "<p>Composer autoload exists: " . (file_exists($vendor_file) ? 'YES' : 'NO') . "</p>";

// Try to load .env manually
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }
    }
    echo "<p>Environment loaded: " . (isset($_ENV['SMTP_HOST']) ? 'YES' : 'NO') . "</p>";
    echo "<p>SMTP Host: " . ($_ENV['SMTP_HOST'] ?? 'Not set') . "</p>";
}

// Try database connection
try {
    $db_host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_name = $_ENV['DB_NAME'] ?? 'wavinyacup';
    $db_user = $_ENV['DB_USER'] ?? 'root';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    echo "<p>Database: Connected ✅</p>";
    
    // Get coach count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM coach_registrations WHERE status = 'approved'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Approved coaches: {$count}</p>";
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Simple form to send one test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    if (file_exists($vendor_file)) {
        try {
            require_once $vendor_file;
            
            use PHPMailer\PHPMailer\PHPMailer;
            
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'];
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->setFrom($_ENV['SMTP_USERNAME'], 'Ultra Simple Test');
            $mail->addAddress('justusmunyoki@gmail.com');
            $mail->Subject = 'Ultra Simple Test - ' . date('H:i:s');
            $mail->Body = 'This is an ultra simple test email sent at ' . date('Y-m-d H:i:s');
            
            $mail->send();
            echo "<p style='color: green;'>✅ Test email sent successfully!</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Email failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ PHPMailer not available</p>";
    }
}

echo "<form method='POST'>";
echo "<button type='submit' name='send_test' style='padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;'>Send Test Email</button>";
echo "</form>";
?>
