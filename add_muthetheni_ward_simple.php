<?php
// Simple script to add Muthetheni ward
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/auth/gmail_oauth.php';

// Check admin permissions
if (!GmailOAuth::isValidAdminSession()) {
    die('Access denied. Admin login required.');
}

$db = db();

// Which sub-county should Muthetheni belong to?
// Please specify the correct sub-county ID:
// 1 = Machakos Town
// 2 = Kangundo  
// 3 = Matungulu
// 4 = Kathiani
// 5 = Mavoko
// 6 = Masinga
// 7 = Yatta
// 8 = Mwala

$sub_county_id = 1; // Change this to the correct sub-county

?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Muthetheni Ward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h3>Add Muthetheni Ward</h3>
        </div>
        <div class="card-body">
<?php
try {
    // Check if it already exists
    $existing = $db->fetchRow("SELECT * FROM wards WHERE name = 'Muthetheni'");
    
    if (!$existing) {
        $db->query(
            "INSERT INTO wards (sub_county_id, name, code) VALUES (?, ?, ?)",
            [$sub_county_id, 'Muthetheni', 'MUTH_' . str_pad($sub_county_id, 3, '0', STR_PAD_LEFT)]
        );
        echo '<div class="alert alert-success">✅ Muthetheni ward added successfully!</div>';
        
        // Get sub-county name
        $sub_county = $db->fetchRow("SELECT name FROM sub_counties WHERE id = ?", [$sub_county_id]);
        echo '<p>Added to Sub-County: ' . htmlspecialchars($sub_county['name']) . '</p>';
    } else {
        echo '<div class="alert alert-info">ℹ️ Muthetheni ward already exists.</div>';
        
        // Show existing details
        $sub_county = $db->fetchRow("SELECT name FROM sub_counties WHERE id = ?", [$existing['sub_county_id']]);
        echo '<p>Existing ward details:</p>';
        echo '<ul>';
        echo '<li>ID: ' . $existing['id'] . '</li>';
        echo '<li>Name: ' . htmlspecialchars($existing['name']) . '</li>';
        echo '<li>Code: ' . htmlspecialchars($existing['code']) . '</li>';
        echo '<li>Sub-County: ' . htmlspecialchars($sub_county['name']) . '</li>';
        echo '</ul>';
    }
    
    // Show all wards for verification
    echo '<h4 class="mt-4">All Wards:</h4>';
    $all_wards = $db->fetchAll("
        SELECT w.*, sc.name as sub_county_name 
        FROM wards w 
        JOIN sub_counties sc ON w.sub_county_id = sc.id 
        ORDER BY sc.name, w.name
    ");
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Ward Name</th><th>Code</th><th>Sub-County</th></tr></thead>';
    echo '<tbody>';
    foreach ($all_wards as $ward) {
        $highlight = ($ward['name'] === 'Muthetheni') ? 'table-warning' : '';
        echo "<tr class='{$highlight}'>";
        echo '<td>' . htmlspecialchars($ward['name']) . '</td>';
        echo '<td>' . htmlspecialchars($ward['code']) . '</td>';
        echo '<td>' . htmlspecialchars($ward['sub_county_name']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
            <div class="mt-3">
                <a href="admin/dashboard.php" class="btn btn-primary">Back to Admin Dashboard</a>
                <a href="admin/coaches/manage_coach.php?id=<?php echo $_GET['coach_id'] ?? ''; ?>" class="btn btn-secondary">Back to Coach Management</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
