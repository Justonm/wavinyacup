<?php
// gmail_bulk_sender.php - Generate Gmail-compatible bulk email format
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'gmailbulk2025') {
    die('Access denied - use ?access=gmailbulk2025');
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

if ($action === 'download_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="coach_emails_for_gmail.csv"');
    
    echo "Email,Subject,Body\n";
    foreach ($log_data as $entry) {
        $body = str_replace('"', '""', strip_tags($entry['body'])); // Escape quotes and remove HTML
        echo '"' . $entry['email'] . '","' . $entry['subject'] . '","' . $body . '"' . "\n";
    }
    exit;
}

if ($action === 'download_contacts') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="coach_contacts.csv"');
    
    echo "Name,Email Address\n";
    foreach ($log_data as $entry) {
        echo '"' . $entry['coach_name'] . '","' . $entry['email'] . '"' . "\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Bulk Email Helper</title>
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
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .method-card {
            border: 1px solid #e9ecef; border-radius: 8px; margin: 20px 0;
            background: #f8f9fa; padding: 20px;
        }
        .method-title {
            color: #2c3e50; font-size: 18px; font-weight: 600; margin-bottom: 15px;
        }
        .step-list {
            list-style: none; padding: 0;
        }
        .step-list li {
            padding: 8px 0; border-bottom: 1px solid #e9ecef;
            position: relative; padding-left: 30px;
        }
        .step-list li:before {
            content: counter(step-counter); counter-increment: step-counter;
            position: absolute; left: 0; top: 8px;
            background: #007bff; color: white; border-radius: 50%;
            width: 20px; height: 20px; display: flex; align-items: center;
            justify-content: center; font-size: 12px; font-weight: bold;
        }
        .step-list { counter-reset: step-counter; }
        .email-preview {
            background: white; border: 1px solid #e9ecef; border-radius: 6px;
            padding: 15px; margin: 10px 0; max-height: 200px; overflow-y: auto;
        }
        .credential-highlight {
            background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .back-link { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="email_logger_system.php?access=emaillog2025" class="btn">← Back to Email Logger</a>
        </div>
        
        <h1>📧 Gmail Bulk Email Helper</h1>
        
        <div style="text-align: center; margin: 20px 0;">
            <p><strong>Total Emails to Send:</strong> <?= count($log_data) ?></p>
            <p><strong>Log File:</strong> <?= htmlspecialchars($file) ?></p>
        </div>
        
        <!-- Method 1: Gmail Mail Merge -->
        <div class="method-card">
            <div class="method-title">🎯 Method 1: Gmail Mail Merge (Recommended)</div>
            <p>Use Gmail's mail merge feature with Google Sheets to send personalized emails to all coaches at once.</p>
            
            <div style="text-align: center; margin: 15px 0;">
                <a href="?file=<?= urlencode($file) ?>&access=gmailbulk2025&action=download_csv" class="btn btn-success">📥 Download Email Data (CSV)</a>
            </div>
            
            <ol class="step-list">
                <li>Download the CSV file above</li>
                <li>Open Google Sheets and import the CSV</li>
                <li>Install "Yet Another Mail Merge" add-on from Google Workspace Marketplace</li>
                <li>Use the add-on to send personalized emails to all coaches</li>
                <li>Each coach gets their unique password and login details</li>
            </ol>
        </div>
        
        <!-- Method 2: Gmail BCC -->
        <div class="method-card">
            <div class="method-title">📮 Method 2: Gmail BCC (Quick but Generic)</div>
            <p>Send one email to all coaches using BCC, but each coach will need individual password sharing.</p>
            
            <div style="text-align: center; margin: 15px 0;">
                <a href="?file=<?= urlencode($file) ?>&access=gmailbulk2025&action=download_contacts" class="btn btn-warning">📥 Download Contact List</a>
            </div>
            
            <ol class="step-list">
                <li>Download the contact list above</li>
                <li>Import contacts to Gmail</li>
                <li>Compose one generic welcome email</li>
                <li>Add all coaches in BCC field</li>
                <li>Send individual password emails separately</li>
            </ol>
        </div>
        
        <!-- Method 3: Copy-Paste Individual -->
        <div class="method-card">
            <div class="method-title">✂️ Method 3: Individual Copy-Paste</div>
            <p>Copy each email content and send individually - most time consuming but most personalized.</p>
            
            <ol class="step-list">
                <li>Open Gmail compose window</li>
                <li>Copy email content from the log viewer</li>
                <li>Paste and send to each coach individually</li>
                <li>Repeat for all <?= count($log_data) ?> coaches</li>
            </ol>
        </div>
        
        <!-- Email Previews -->
        <h3>📋 Email Content Preview</h3>
        <?php foreach (array_slice($log_data, 0, 2) as $index => $entry): ?>
        <div class="email-preview">
            <h4>Coach: <?= htmlspecialchars($entry['coach_name']) ?></h4>
            <p><strong>To:</strong> <?= htmlspecialchars($entry['email']) ?></p>
            <p><strong>Subject:</strong> <?= htmlspecialchars($entry['subject']) ?></p>
            
            <div class="credential-highlight">
                <strong>🔑 Login Credentials:</strong><br>
                Email: <?= htmlspecialchars($entry['email']) ?><br>
                Password: <code><?= htmlspecialchars($entry['password']) ?></code>
            </div>
            
            <div style="font-size: 12px; color: #6c757d; margin-top: 10px;">
                <?= substr(strip_tags($entry['body']), 0, 200) ?>...
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (count($log_data) > 2): ?>
        <p style="text-align: center; color: #6c757d;">
            ... and <?= count($log_data) - 2 ?> more coaches
        </p>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div style="margin-top: 40px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
            <h4>💡 Recommended Approach</h4>
            <p><strong>For <?= count($log_data) ?> coaches, I recommend Method 1 (Mail Merge):</strong></p>
            <ul>
                <li>✅ Sends all emails at once</li>
                <li>✅ Each coach gets personalized content with their unique password</li>
                <li>✅ Professional appearance</li>
                <li>✅ Tracks delivery status</li>
                <li>✅ Saves significant time</li>
            </ul>
            
            <p><strong>Alternative:</strong> If you prefer Gmail's native features, use Method 2 for the welcome message and send passwords separately.</p>
        </div>
    </div>
</body>
</html>
