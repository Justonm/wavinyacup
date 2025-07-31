<?php
require_once '../config/config.php';

// Check if user has admin permissions
if (!has_permission('all')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get players with team information
$players = $db->fetchAll("
    SELECT p.*, t.name as team_name, w.name as ward_name 
    FROM players p 
    LEFT JOIN teams t ON p.team_id = t.id 
    LEFT JOIN wards w ON p.ward_id = w.id 
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players Management - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-futbol me-2"></i>Machakos Teams
                        </h4>
                        <small class="text-white-50">Admin Dashboard</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="teams.php">
                            <i class="fas fa-users me-2"></i>Teams
                        </a>
                        <a class="nav-link active" href="players.php">
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
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-user me-2"></i>Players Management</h2>
                            <p class="text-muted">Manage all registered players</p>
                        </div>
                        <button class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Player
                        </button>
                    </div>
                    
                    <!-- Players Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">All Players</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($players)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-user fa-3x text-muted mb-3"></i>
                                    <h5>No Players Found</h5>
                                    <p class="text-muted">No players have been registered yet.</p>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Register First Player
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Player Name</th>
                                                <th>Team</th>
                                                <th>Ward</th>
                                                <th>Position</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($players as $player): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($player['email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($player['team_name']): ?>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($player['team_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">No Team</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($player['ward_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($player['position'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $player['is_active'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $player['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger">
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
</body>
</html> 