<?php
// Targeted test for player addition 403 error
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (!is_logged_in() || !has_role('coach')) {
    die('Please log in as a coach to use this test.');
}

$user = get_logged_in_user();
$db = db();

echo "<h2>Player Addition 403 Error Test</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { color: #dc3545; }
.success { color: #28a745; }
.warning { color: #ffc107; }
</style>";

// Get coach and team info
$coach = $db->fetchRow("
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    WHERE c.user_id = ?
", [$user['id']]);

if (!$coach || !$coach['team_id']) {
    die('<div class="error">No team found for this coach.</div>');
}

echo "<div class='test-section'>";
echo "<h3>Testing Team: {$coach['team_name']} (ID: {$coach['team_id']})</h3>";
echo "</div>";

// Test each validation step individually
echo "<div class='test-section'>";
echo "<h3>Step-by-Step Validation Test</h3>";

// Test 1: Basic form data
$test_data = [
    'first_name' => 'Test',
    'last_name' => 'Player',
    'email' => 'test.player@example.com',
    'phone' => '0712345678',
    'id_number' => '99999999',
    'gender' => 'female',
    'date_of_birth' => '1995-01-01',
    'position' => 'forward',
    'jersey_number' => 99,
    'height_cm' => 170,
    'weight_kg' => 60.0,
    'preferred_foot' => 'right',
    'consent' => true
];

echo "<h4>Test 1: Basic Validation</h4>";
$errors = [];

if (empty($test_data['first_name']) || empty($test_data['last_name']) || empty($test_data['gender']) || empty($test_data['date_of_birth']) || empty($test_data['position']) || empty($test_data['id_number'])) {
    $errors[] = 'Required fields missing';
} else {
    echo "<div class='success'>✓ Required fields present</div>";
}

if (!empty($test_data['email']) && !filter_var($test_data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email';
} else {
    echo "<div class='success'>✓ Email valid</div>";
}

if ($test_data['jersey_number'] < 1 || $test_data['jersey_number'] > 99) {
    $errors[] = 'Invalid jersey number';
} else {
    echo "<div class='success'>✓ Jersey number valid</div>";
}

if (!$test_data['consent']) {
    $errors[] = 'Consent not given';
} else {
    echo "<div class='success'>✓ Consent given</div>";
}

echo "<h4>Test 2: Player Count Check</h4>";
$current_player_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$coach['team_id']]);
$max_players = 22;

echo "Current players: $current_player_count/$max_players<br>";

if ($current_player_count >= $max_players) {
    $errors[] = 'Team at maximum capacity';
    echo "<div class='error'>✗ Team at maximum capacity</div>";
} else {
    echo "<div class='success'>✓ Team can accept more players</div>";
}

echo "<h4>Test 3: Duplicate Checks</h4>";

// Check for duplicate ID number
$existing_user_id = $db->fetchRow("SELECT id FROM users WHERE id_number = ?", [$test_data['id_number']]);
if ($existing_user_id) {
    $errors[] = 'Duplicate ID number';
    echo "<div class='error'>✗ ID number {$test_data['id_number']} already exists</div>";
} else {
    echo "<div class='success'>✓ ID number unique</div>";
}

// Check for duplicate email
if (!empty($test_data['email'])) {
    $existing_email = $db->fetchRow("SELECT id FROM users WHERE email = ?", [$test_data['email']]);
    if ($existing_email) {
        $errors[] = 'Duplicate email';
        echo "<div class='error'>✗ Email {$test_data['email']} already exists</div>";
    } else {
        echo "<div class='success'>✓ Email unique</div>";
    }
}

// Check for duplicate jersey number
$existing_jersey = $db->fetchRow("SELECT id FROM players WHERE team_id = ? AND jersey_number = ?", [$coach['team_id'], $test_data['jersey_number']]);
if ($existing_jersey) {
    $errors[] = 'Duplicate jersey number';
    echo "<div class='error'>✗ Jersey number {$test_data['jersey_number']} already taken</div>";
} else {
    echo "<div class='success'>✓ Jersey number available</div>";
}

echo "<h4>Test 4: Database Transaction Test</h4>";
if (empty($errors)) {
    echo "<div class='success'>✓ All validations passed - ready for database insertion</div>";
    
    // Test database transaction without actually inserting
    try {
        $db->beginTransaction();
        
        // Test username generation
        $username = strtolower($test_data['first_name'] . '.' . $test_data['last_name'] . '.' . time());
        echo "<div class='success'>✓ Username generated: $username</div>";
        
        // Test password hash
        $password_hash = password_hash('player123', PASSWORD_DEFAULT);
        echo "<div class='success'>✓ Password hash generated</div>";
        
        $db->rollBack(); // Don't actually insert
        echo "<div class='success'>✓ Database transaction test successful</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='error'>✗ Database transaction failed: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>✗ Validation errors found:</div>";
    foreach ($errors as $error) {
        echo "<div class='error'>- $error</div>";
    }
}
echo "</div>";

// Test the actual form submission simulation
echo "<div class='test-section'>";
echo "<h3>Form Submission Test</h3>";
echo "<p>Try this form with the test data to see if it triggers the 403:</p>";

echo '<form method="POST" action="coach/manage_team.php" target="_blank">';
echo '<input type="hidden" name="action" value="add_player">';
foreach ($test_data as $key => $value) {
    if ($key === 'consent') {
        echo '<input type="hidden" name="consent" value="1">';
    } else {
        echo '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
    }
}
echo '<button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">Test Form Submission (Opens in new tab)</button>';
echo '</form>';

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Click the button above to test form submission</li>";
echo "<li>If you get 403, the issue is in the form processing logic</li>";
echo "<li>If it works, try with your actual player data</li>";
echo "<li>Compare what's different between test data and your real data</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='coach/manage_team.php'>Back to Manage Team</a></p>";
?>
