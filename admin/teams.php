<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user is logged in and has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get teams with ward and sub-county information
$teams = $db->fetchAll("
    SELECT t.*, w.name as ward_name, sc.name as sub_county_name 
    FROM teams t 
    JOIN wards w ON t.ward_id = w.id 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Management - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
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
        .btn {
            cursor: pointer;
            pointer-events: auto;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
                        <a class="nav-link active" href="teams.php">
                            <i class="fas fa-users me-2"></i>Teams
                        </a>
                        <a class="nav-link" href="players.php">
                            <i class="fas fa-user me-2"></i>Players
                        </a>
                        <a class="nav-link" href="coaches.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Coaches
                        </a>
                        <a class="nav-link" href="registrations.php">
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
                            <h2><i class="fas fa-users me-2"></i>Teams Management</h2>
                            <p class="text-muted">Manage all registered teams</p>
                        </div>
                        <a href="../teams/register.php" class="btn btn-primary" onclick="console.log('Add New Team clicked')">
                            <i class="fas fa-plus me-2"></i>Add New Team
                        </a>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">All Teams</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teams)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No Teams Found</h5>
                                    <p class="text-muted">No teams have been registered yet.</p>
                                    <a href="../teams/register.php" class="btn btn-primary" onclick="console.log('Register First Team clicked')">
                                        <i class="fas fa-plus me-2"></i>Register First Team
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Team Name</th>
                                                <th>Ward</th>
                                                <th>Sub-County</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teams as $team): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($team['team_code']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($team['ward_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($team['sub_county_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $team['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($team['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo format_date($team['created_at']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Teams page loaded');
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    console.log('Button clicked:', this.textContent.trim());
                    console.log('Button href:', this.href);
                });
            });
        });
    </script>
</body>
</html>