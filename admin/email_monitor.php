<?php
// email_monitor.php - Monitor email queue and cron job status
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['access']) || $_GET['access'] !== 'monitor2025') {
    die('Access denied - use ?access=monitor2025');
}

// Load environment variables
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}", 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'run_cron') {
        try {
            // Try to run the cron job manually
            $cron_file = dirname(__DIR__) . '/cron/send_queued_emails.php';
            if (file_exists($cron_file)) {
                ob_start();
                include $cron_file;
                $output = ob_get_clean();
                $message = "Cron job executed. Output: " . ($output ?: "No output");
            } else {
                $message = "Cron job file not found at: {$cron_file}";
            }
        } catch (Exception $e) {
            $message = "Error running cron job: " . $e->getMessage();
        }
    }
}

// Get email queue statistics
$stats = [];
try {
    $stats['pending'] = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")->fetch()['count'];
    $stats['sent'] = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'sent'")->fetch()['count'];
    $stats['failed'] = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'")->fetch()['count'];
    $stats['total'] = $stats['pending'] + $stats['sent'] + $stats['failed'];
} catch (Exception $e) {
    $stats = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'total' => 0];
}

// Get recent emails
$recent_emails = [];
try {
    $stmt = $pdo->query("
        SELECT recipient, subject, status, created_at, sent_at, attempts 
        FROM email_queue 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $recent_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignore errors
}

// Check cron job files
$cron_files = [
    'send_queued_emails.php' => dirname(__DIR__) . '/cron/send_queued_emails.php',
    'process_approvals.php' => dirname(__DIR__) . '/cron/process_approved_coaches.php'
];

$cron_status = [];
foreach ($cron_files as $name => $path) {
    $cron_status[$name] = [
        'exists' => file_exists($path),
        'readable' => file_exists($path) && is_readable($path),
        'size' => file_exists($path) ? filesize($path) : 0,
        'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System Monitor</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f8f9fa; min-height: 100vh;
        }
        .container {
            max-width: 1200px; margin: 0 auto; background: white; padding: 30px;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin: 30px 0;
        }
        .stat-card {
            padding: 25px; border-radius: 10px; text-align: center; color: white;
        }
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.sent { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.failed { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card.total { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-number { font-size: 2.5em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; }
        .btn { 
            padding: 12px 24px; margin: 8px; background: #007bff; color: white; border: none;
            cursor: pointer; border-radius: 6px; font-size: 14px; transition: all 0.3s ease;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .message { 
            padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 14px;
            background: #d4edda; color: #155724; border-left: 4px solid #28a745;
        }
        .error { background: #f8d7da; color: #721c24; border-left-color: #dc3545; }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            background: white; border-radius: 8px; overflow: hidden;
        }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status-badge {
            padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-sent { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .cron-status {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px; margin: 20px 0;
        }
        .cron-card {
            padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Email System Monitor</h1>
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, 'Error') || str_contains($message, 'not found') ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Email Queue Statistics -->
        <h3>📈 Email Queue Statistics</h3>
        <div class="stats">
            <div class="stat-card pending">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card sent">
                <div class="stat-number"><?= $stats['sent'] ?></div>
                <div class="stat-label">Sent</div>
            </div>
            <div class="stat-card failed">
                <div class="stat-number"><?= $stats['failed'] ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card total">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        
        <!-- Actions -->
        <div style="text-align: center; margin: 30px 0;">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="run_cron">
                <button type="submit" class="btn btn-success">⚡ Run Cron Job Manually</button>
            </form>
            <button onclick="location.reload()" class="btn">🔄 Refresh Status</button>
        </div>
        
        <!-- Cron Job Status -->
        <h3>⚙️ Cron Job Status</h3>
        <div class="cron-status">
            <?php foreach ($cron_status as $name => $status): ?>
            <div class="cron-card">
                <h4><?= htmlspecialchars($name) ?></h4>
                <p><strong>Exists:</strong> <?= $status['exists'] ? '✅ Yes' : '❌ No' ?></p>
                <p><strong>Readable:</strong> <?= $status['readable'] ? '✅ Yes' : '❌ No' ?></p>
                <p><strong>Size:</strong> <?= $status['size'] ?> bytes</p>
                <p><strong>Last Modified:</strong> <?= $status['modified'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Recent Emails -->
        <h3>📧 Recent Emails (Last 20)</h3>
        <?php if (count($recent_emails) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Sent</th>
                    <th>Attempts</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_emails as $email): ?>
                <tr>
                    <td><?= htmlspecialchars($email['recipient']) ?></td>
                    <td><?= htmlspecialchars(substr($email['subject'], 0, 50)) ?><?= strlen($email['subject']) > 50 ? '...' : '' ?></td>
                    <td><span class="status-badge status-<?= $email['status'] ?>"><?= htmlspecialchars($email['status']) ?></span></td>
                    <td><?= date('M j, H:i', strtotime($email['created_at'])) ?></td>
                    <td><?= $email['sent_at'] ? date('M j, H:i', strtotime($email['sent_at'])) : '-' ?></td>
                    <td><?= $email['attempts'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No emails found in queue.</p>
        <?php endif; ?>
        
        <!-- System Info -->
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4>ℹ️ System Information</h4>
            <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s T') ?></p>
            <p><strong>SMTP Host:</strong> <?= $_ENV['SMTP_HOST'] ?? 'Not configured' ?></p>
            <p><strong>SMTP Username:</strong> <?= $_ENV['SMTP_USERNAME'] ?? 'Not configured' ?></p>
            <p><strong>Database:</strong> Connected ✅</p>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
