<?php
// view_email_log.php - View generated email log files
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'viewlog2025') {
    die('Access denied - use ?access=viewlog2025');
}

$file = $_GET['file'] ?? '';
if (empty($file)) {
    die('No file specified');
}

$log_file = dirname(__DIR__) . '/logs/' . basename($file);
if (!file_exists($log_file) || !str_contains($file, 'coach_credentials_')) {
    die('File not found or invalid');
}

$log_data = json_decode(file_get_contents($log_file), true);
if (!$log_data) {
    die('Invalid log file format');
}

$action = $_GET['action'] ?? '';
if ($action === 'download_txt') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . str_replace('.json', '.txt', $file) . '"');
    
    foreach ($log_data as $entry) {
        echo "=" . str_repeat("=", 80) . "\n";
        echo "COACH: " . $entry['coach_name'] . "\n";
        echo "EMAIL: " . $entry['email'] . "\n";
        echo "PASSWORD: " . $entry['password'] . "\n";
        echo "TIMESTAMP: " . $entry['timestamp'] . "\n";
        echo "SUBJECT: " . $entry['subject'] . "\n";
        echo "=" . str_repeat("=", 80) . "\n\n";
        echo strip_tags($entry['body']) . "\n\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Email Log</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f8f9fa; min-height: 100vh;
        }
        .container {
            max-width: 1000px; margin: 0 auto; background: white; padding: 30px;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .btn { 
            padding: 12px 24px; margin: 8px; background: #007bff; color: white; border: none;
            cursor: pointer; border-radius: 6px; font-size: 14px; transition: all 0.3s ease;
            text-decoration: none; display: inline-block;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .email-entry {
            border: 1px solid #e9ecef; border-radius: 8px; margin: 20px 0;
            background: #f8f9fa;
        }
        .email-header {
            padding: 15px 20px; background: #e9ecef; border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        .email-body {
            padding: 20px; background: white; border-radius: 0 0 8px 8px;
        }
        .credential-box {
            background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 10px 0;
            border-left: 4px solid #007bff;
        }
        .password {
            font-family: monospace; background: #f8f9fa; padding: 4px 8px;
            border-radius: 4px; font-size: 16px; font-weight: bold;
        }
        .back-link { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="email_logger_system.php?access=emaillog2025" class="btn">← Back to Email Logger</a>
            <a href="?file=<?= urlencode($file) ?>&access=viewlog2025&action=download_txt" class="btn btn-success">📥 Download as Text</a>
        </div>
        
        <h1>📧 Email Log: <?= htmlspecialchars($file) ?></h1>
        
        <div style="text-align: center; margin: 20px 0;">
            <p><strong>Total Emails:</strong> <?= count($log_data) ?></p>
            <p><strong>Generated:</strong> <?= $log_data[0]['timestamp'] ?? 'Unknown' ?></p>
        </div>
        
        <?php foreach ($log_data as $index => $entry): ?>
        <div class="email-entry">
            <div class="email-header">
                <h3>Email #<?= $index + 1 ?> - <?= htmlspecialchars($entry['coach_name']) ?></h3>
                <div class="credential-box">
                    <p><strong>📧 To:</strong> <?= htmlspecialchars($entry['email']) ?></p>
                    <p><strong>🔑 Password:</strong> <span class="password"><?= htmlspecialchars($entry['password']) ?></span></p>
                    <p><strong>📅 Generated:</strong> <?= htmlspecialchars($entry['timestamp']) ?></p>
                </div>
            </div>
            <div class="email-body">
                <h4>Subject: <?= htmlspecialchars($entry['subject']) ?></h4>
                <div style="border: 1px solid #e9ecef; padding: 15px; border-radius: 6px; background: #fafafa;">
                    <?= $entry['body'] ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 40px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
            <h4>📋 How to Send These Emails</h4>
            <ol>
                <li><strong>Copy Email Content:</strong> Copy the HTML content from each email above</li>
                <li><strong>Use Your Email Client:</strong> Gmail, Outlook, or any email service</li>
                <li><strong>Send to Each Coach:</strong> Use the email addresses shown</li>
                <li><strong>Include Passwords:</strong> Make sure each coach gets their unique password</li>
                <li><strong>Mark as Sent:</strong> Return to the logger system and mark emails as sent</li>
            </ol>
            
            <p><strong>💡 Tip:</strong> You can download this as a text file for easier copying and pasting.</p>
        </div>
    </div>
</body>
</html>
