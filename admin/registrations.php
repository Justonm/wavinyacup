<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../auth/gmail_oauth.php';

// Check if user has admin permissions using the new GmailOAuth class
if (!GmailOAuth::isValidAdminSession()) {
    redirect('../auth/admin_login.php');
}

$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Handle registration actions (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = (int)($_POST['registration_id'] ?? 0);
    $action = sanitize_input($_POST['action'] ?? '');

    if ($registration_id > 0 && ($action === 'approve' || $action === 'reject')) {
        try {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            $db->beginTransaction();

            $db->query("UPDATE team_registrations SET status = ? WHERE id = ?", [$status, $registration_id]);

            if ($status === 'approved') {
                $registration = $db->fetch("SELECT * FROM team_registrations WHERE id = ?", [$registration_id]);

                $db->query("
                    INSERT INTO teams (name, description, team_logo, ward_id, sub_county_id, status)
                    VALUES (?, ?, ?, ?, ?, 'active')
                ", [
                    $registration['team_name'],
                    $registration['team_description'],
                    $registration['team_logo'],
                    $registration['ward_id'],
                    $registration['sub_county_id']
                ]);

                $new_team_id = $db->lastInsertId();
                $db->query("UPDATE team_registrations SET team_id = ? WHERE id = ?", [$new_team_id, $registration_id]);
            }

            $db->commit();
            $success = "Registration has been " . $status . " successfully.";
            log_activity($user['id'], 'registration_update', "{$action} registration ID: {$registration_id}");

        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to {$action} registration. Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid action or registration ID.";
    }
}

// Get registrations with team and ward information
$registrations = $db->fetchAll("
    SELECT tr.*, 
    COALESCE(tr.team_name, t.name) as team_name,
    w.name as ward_name,
    sc.name as sub_county_name
    FROM team_registrations tr 
    LEFT JOIN teams t ON tr.team_id = t.id 
    LEFT JOIN wards w ON tr.ward_id = w.id 
    LEFT JOIN sub_counties sc ON tr.sub_county_id = sc.id
    ORDER BY tr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Registrations Management'; include dirname(__DIR__) . '/includes/head.php'; ?>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar p-3">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo.png" alt="Governor Wavinya Cup 3rd Edition Logo" style="width: 120px; height: auto;" class="mb-2">
                    <h5 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h5>
                    <small class="text-white-50">Admin Dashboard</small>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="teams.php">
                        <i class="fas fa-users me-2"></i>Teams
                    </a>
                    <a class="nav-link" href="players.php">
                        <i class="fas fa-user me-2"></i>Players
                    </a>
                    <a class="nav-link" href="coaches.php">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Coaches
                    </a>
                    <a class="nav-link active" href="registrations.php">
                        <i class="fas fa-clipboard-list me-2"></i>Registrations
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-clipboard-list me-2"></i>Registrations Management</h2>
                        <p class="text-muted">Manage team registrations and approvals</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Registrations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registrations)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5>No Registrations Found</h5>
                                <p class="text-muted">No team registrations have been submitted yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Team Name</th>
                                            <th>Ward</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $reg): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($reg['team_name'] ?? 'N/A'); ?></strong></td>
                                                <td><?php echo htmlspecialchars($reg['ward_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $reg['status'] === 'pending' ? 'warning' : 
                                                            ($reg['status'] === 'approved' ? 'success' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($reg['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo format_date($reg['created_at']); ?></td>
                                                <td>
                                                    <a href="view_registration.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($reg['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-outline-success" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger" title="Reject">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>