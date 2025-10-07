<?php
// admin/run_sql_fix.php - Execute SQL commands to fix coach ward assignment
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../auth/gmail_oauth.php';

// Check admin permissions
if (!GmailOAuth::isValidAdminSession()) {
    redirect('../auth/admin_login.php');
}

$db = db();
$results = [];

try {
    // 1. Check current Muthetheni ward info
    $results[] = ['title' => 'Current Muthetheni ward info:', 'data' => $db->fetchAll("
        SELECT w.id, w.name, w.code, w.sub_county_id, sc.name as sub_county_name 
        FROM wards w 
        JOIN sub_counties sc ON w.sub_county_id = sc.id 
        WHERE w.name = 'Muthetheni'
    ")];

    // 2. Check Mwala sub-county ID
    $mwala = $db->fetchRow("SELECT id, name, code FROM sub_counties WHERE name = 'Mwala'");
    $results[] = ['title' => 'Mwala sub-county info:', 'data' => [$mwala]];

    if ($mwala) {
        // 3. Update Muthetheni ward to be in Mwala sub-county
        $db->query("UPDATE wards SET sub_county_id = ?, code = ? WHERE name = 'Muthetheni'", 
                   [$mwala['id'], 'MUTH_MWL']);
        $results[] = ['title' => 'Ward update:', 'data' => [['status' => 'Muthetheni ward moved to Mwala']]];

        // 4. Check coach ID 65 current registration
        $coach_reg = $db->fetchAll("
            SELECT cr.*, w.name as ward_name, sc.name as sub_county_name
            FROM coach_registrations cr
            LEFT JOIN wards w ON cr.ward_id = w.id
            LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
            WHERE cr.user_id = 65
        ");
        $results[] = ['title' => 'Coach 65 current registration:', 'data' => $coach_reg];

        // 5. Get the updated Muthetheni ward ID
        $muthetheni_ward = $db->fetchRow("SELECT id, name, code, sub_county_id FROM wards WHERE name = 'Muthetheni'");
        $results[] = ['title' => 'Updated Muthetheni ward:', 'data' => [$muthetheni_ward]];

        if ($muthetheni_ward) {
            // 6. Update or insert coach registration for coach ID 65
            $existing_reg = $db->fetchRow("SELECT id FROM coach_registrations WHERE user_id = 65");
            
            if ($existing_reg) {
                // Update existing registration
                $db->query("UPDATE coach_registrations SET ward_id = ?, updated_at = NOW() WHERE user_id = 65", 
                          [$muthetheni_ward['id']]);
                $results[] = ['title' => 'Registration update:', 'data' => [['status' => 'Updated existing registration']]];
            } else {
                // Insert new registration
                $db->query("INSERT INTO coach_registrations (user_id, ward_id, status, created_at, updated_at) VALUES (65, ?, 'approved', NOW(), NOW())", 
                          [$muthetheni_ward['id']]);
                $results[] = ['title' => 'Registration insert:', 'data' => [['status' => 'Created new registration']]];
            }

            // 7. Final verification
            $final_check = $db->fetchAll("
                SELECT 
                    u.id as coach_id,
                    u.first_name,
                    u.last_name,
                    w.name as ward_name,
                    sc.name as sub_county_name,
                    cr.status
                FROM users u
                JOIN coach_registrations cr ON u.id = cr.user_id
                JOIN wards w ON cr.ward_id = w.id
                JOIN sub_counties sc ON w.sub_county_id = sc.id
                WHERE u.id = 65
            ");
            $results[] = ['title' => 'Final verification:', 'data' => $final_check];
        }
    }

} catch (Exception $e) {
    $results[] = ['title' => 'Error:', 'data' => [['error' => $e->getMessage()]]];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Fix Results - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-database me-2"></i>SQL Fix Results</h3>
        </div>
        <div class="card-body">
            <?php foreach ($results as $result): ?>
                <div class="mb-4">
                    <h5><?php echo htmlspecialchars($result['title']); ?></h5>
                    <?php if (!empty($result['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <?php if (!empty($result['data'][0])): ?>
                                            <?php foreach (array_keys($result['data'][0]) as $key): ?>
                                                <th><?php echo htmlspecialchars($key); ?></th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['data'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No data</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="mt-4">
                <a href="coaches/view_coach.php?id=65" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>View Coach Profile
                </a>
                <a href="coaches/manage_coach.php?id=65" class="btn btn-secondary">
                    <i class="fas fa-edit me-2"></i>Edit Coach
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
