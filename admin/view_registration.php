<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user has admin permissions
if (!is_logged_in() || !has_permission('all')) {
    redirect('../auth/login.php');
}

$db = db();
$error = '';
$registration = null;

// Get the registration ID from the URL
$registration_id = (int)($_GET['id'] ?? 0);

if ($registration_id > 0) {
    // Fetch detailed registration data
    $registration = $db->fetch("
        SELECT tr.*, t.name as team_name, w.name as ward_name, sc.name as sub_county_name
        FROM team_registrations tr
        LEFT JOIN teams t ON tr.team_id = t.id
        LEFT JOIN wards w ON tr.ward_id = w.id
        LEFT JOIN sub_counties sc ON tr.sub_county_id = sc.id
        WHERE tr.id = ?
    ", [$registration_id]);

    if (!$registration) {
        $error = "Registration not found.";
    }
} else {
    $error = "Invalid registration ID.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registration - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .detail-card .list-group-item strong {
            display: inline-block;
            width: 150px;
        }
        .img-thumbnail {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar p-3">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo.png" alt="Governor Wavinya Cup Logo" style="width: 120px; height: auto;" class="mb-2">
                    <h5 class="text-white mb-0">Governor Wavinya Cup</h5>
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
                        <h2><i class="fas fa-clipboard-list me-2"></i>View Team Registration</h2>
                        <p class="text-muted">Details for registration #<?php echo htmlspecialchars($registration_id); ?></p>
                    </div>
                    <a href="registrations.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Registrations
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php elseif ($registration): ?>
                    <div class="card detail-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Registration Details for "<?php echo htmlspecialchars($registration['team_name'] ?? 'N/A'); ?>"</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Team Name:</strong> <?php echo htmlspecialchars($registration['team_name']); ?></li>
                                        <li class="list-group-item"><strong>Ward:</strong> <?php echo htmlspecialchars($registration['ward_name'] ?? 'N/A'); ?></li>
                                        <li class="list-group-item"><strong>Sub County:</strong> <?php echo htmlspecialchars($registration['sub_county_name'] ?? 'N/A'); ?></li>
                                        <li class="list-group-item"><strong>Status:</strong>
                                            <span class="badge bg-<?php 
                                                echo $registration['status'] === 'pending' ? 'warning' : 
                                                    ($registration['status'] === 'approved' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($registration['status']); ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item"><strong>Submitted On:</strong> <?php echo format_date($registration['created_at']); ?></li>
                                        <li class="list-group-item"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($registration['team_description'])); ?></li>
                                        <li class="list-group-item"><strong>Owner Name:</strong> <?php echo htmlspecialchars($registration['owner_name']); ?></li>
                                        <li class="list-group-item"><strong>Owner ID:</strong> <?php echo htmlspecialchars($registration['owner_id_number']); ?></li>
                                        <li class="list-group-item"><strong>Owner Phone:</strong> <?php echo htmlspecialchars($registration['owner_phone']); ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="card mb-3">
                                        <div class="card-header">Team Logo</div>
                                        <div class="card-body">
                                            <?php if (!empty($registration['team_logo'])): ?>
                                                <img src="<?php echo htmlspecialchars($registration['team_logo']); ?>" alt="Team Logo" class="img-thumbnail">
                                            <?php else: ?>
                                                <i class="fas fa-image fa-5x text-muted my-3"></i>
                                                <p class="text-muted">No logo uploaded</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <form method="POST" action="registrations.php" class="d-inline">
                                <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                <?php if ($registration['status'] === 'pending'): ?>
                                    <button type="submit" name="action" value="approve" class="btn btn-success me-2">
                                        <i class="fas fa-check me-2"></i>Approve Registration
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                                        <i class="fas fa-times me-2"></i>Reject Registration
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">This registration has already been <?php echo htmlspecialchars($registration['status']); ?>.</span>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>