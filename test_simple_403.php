<?php
// Simple 403 test - minimal code to isolate the issue
echo "<h2>Simple 403 Test</h2>";

// Test 1: Basic access
echo "<h3>Test 1: Basic Page Access</h3>";
echo "✓ This page loads successfully<br>";

// Test 2: POST simulation
echo "<h3>Test 2: POST Request Test</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✓ POST request received<br>";
    echo "Action: " . ($_POST['action'] ?? 'none') . "<br>";
} else {
    echo '<form method="POST">
        <input type="hidden" name="action" value="test_post">
        <button type="submit">Test POST Request</button>
    </form>';
}

// Test 3: File upload simulation
echo "<h3>Test 3: File Upload Test</h3>";
if (isset($_FILES['test_file'])) {
    echo "✓ File upload attempted<br>";
    echo "File error code: " . $_FILES['test_file']['error'] . "<br>";
} else {
    echo '<form method="POST" enctype="multipart/form-data">
        <input type="file" name="test_file" accept="image/*">
        <button type="submit">Test File Upload</button>
    </form>';
}

// Test 4: Check server limits
echo "<h3>Test 4: Server Limits</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// Test 5: Check if manage_team.php is accessible
echo "<h3>Test 5: File Access Test</h3>";
if (file_exists('coach/manage_team.php')) {
    echo "✓ coach/manage_team.php exists<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms('coach/manage_team.php')), -4) . "<br>";
} else {
    echo "✗ coach/manage_team.php not found<br>";
}

// Test 6: Direct URL test
echo "<h3>Test 6: Direct Access Links</h3>";
echo '<a href="coach/manage_team.php" target="_blank">Test coach/manage_team.php</a><br>';
echo '<a href="captain/add_players.php" target="_blank">Test captain/add_players.php</a><br>';

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Run the POST test above</li>";
echo "<li>Try the file upload test</li>";
echo "<li>Click the direct access links</li>";
echo "<li>Check which specific action triggers the 403 error</li>";
echo "</ol>";
?>
