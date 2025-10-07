<?php
// Real-time log monitoring for 403 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Server Log Monitor - 403 Error Analysis</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.log-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { color: #dc3545; }
.warning { color: #ffc107; }
.info { color: #17a2b8; }
pre { background: #343a40; color: #fff; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Function to get recent log entries
function getRecentLogEntries($logPath, $lines = 50) {
    if (!file_exists($logPath) || !is_readable($logPath)) {
        return false;
    }
    
    $content = file_get_contents($logPath);
    $logLines = explode("\n", $content);
    $recentLines = array_slice($logLines, -$lines);
    
    return array_filter($recentLines, function($line) {
        return !empty(trim($line));
    });
}

// Check multiple possible log locations
$logPaths = [
    'error_log',
    '../error_log', 
    '/home/pzjrozek/public_html/error_log',
    '/home/pzjrozek/public_html/wavinyacup/error_log',
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log'
];

echo "<div class='log-section'>";
echo "<h3>1. Error Log Locations Check</h3>";
foreach ($logPaths as $path) {
    if ($path && file_exists($path)) {
        $size = filesize($path);
        $readable = is_readable($path) ? 'readable' : 'not readable';
        echo "<div class='info'>✓ Found: $path ($size bytes, $readable)</div>";
    } else {
        echo "<div>✗ Not found: $path</div>";
    }
}
echo "</div>";

// Get recent errors from available logs
echo "<div class='log-section'>";
echo "<h3>2. Recent Error Log Entries (Last 30 lines)</h3>";

$foundLogs = false;
foreach ($logPaths as $logPath) {
    if ($logPath && file_exists($logPath) && is_readable($logPath)) {
        echo "<h4>Log: $logPath</h4>";
        $entries = getRecentLogEntries($logPath, 30);
        
        if ($entries) {
            echo "<pre>";
            foreach ($entries as $entry) {
                if (stripos($entry, '403') !== false || stripos($entry, 'forbidden') !== false) {
                    echo "<span class='error'>$entry</span>\n";
                } elseif (stripos($entry, 'warning') !== false) {
                    echo "<span class='warning'>$entry</span>\n";
                } else {
                    echo "$entry\n";
                }
            }
            echo "</pre>";
            $foundLogs = true;
        }
        break; // Use first available log
    }
}

if (!$foundLogs) {
    echo "<div class='error'>No accessible error logs found</div>";
}
echo "</div>";

// Check Apache/Nginx access logs for 403 entries
echo "<div class='log-section'>";
echo "<h3>3. Access Log Analysis</h3>";
$accessLogs = [
    '/var/log/apache2/access.log',
    '/var/log/nginx/access.log',
    '/home/pzjrozek/access-logs/governorwavinyacup.com.log'
];

foreach ($accessLogs as $accessLog) {
    if (file_exists($accessLog) && is_readable($accessLog)) {
        echo "<h4>Checking: $accessLog</h4>";
        // Get last 20 lines and filter for 403 errors
        $cmd = "tail -20 '$accessLog' | grep '403'";
        $output = shell_exec($cmd);
        if ($output) {
            echo "<pre>$output</pre>";
        } else {
            echo "<div>No recent 403 errors in access log</div>";
        }
        break;
    }
}
echo "</div>";

// Check current PHP configuration
echo "<div class='log-section'>";
echo "<h3>4. Current PHP Configuration</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
echo "Log Errors: " . ini_get('log_errors') . "<br>";
echo "Error Log: " . ini_get('error_log') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "</div>";

// Test session functionality
echo "<div class='log-section'>";
echo "<h3>5. Session Test</h3>";
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
        echo "<div class='info'>✓ Session started successfully</div>";
    } else {
        echo "<div class='error'>✗ Headers already sent, cannot start session</div>";
    }
} else {
    echo "<div class='info'>✓ Session already active</div>";
}

echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "</div>";

// Test file permissions
echo "<div class='log-section'>";
echo "<h3>6. File Permissions Check</h3>";
$filesToCheck = [
    'coach/manage_team.php',
    'uploads/',
    'uploads/players/',
    'uploads/id/',
    'sessions/'
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $writable = is_writable($file) ? 'writable' : 'not writable';
        echo "$file: $perms ($writable)<br>";
    } else {
        echo "$file: not found<br>";
    }
}
echo "</div>";

echo "<div class='log-section'>";
echo "<h3>7. Next Steps</h3>";
echo "<ol>";
echo "<li>Try adding a player again and immediately refresh this page</li>";
echo "<li>Look for new error entries in the logs above</li>";
echo "<li>Check if specific form fields or file uploads trigger the 403</li>";
echo "<li>Try adding a player without any file uploads</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='coach/manage_team.php'>Go to Manage Team</a> | <a href='javascript:location.reload()'>Refresh This Page</a></p>";
?>
