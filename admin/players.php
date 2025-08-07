<?php
require_once '../config/config.php';

// Check if user has admin permissions
if (!is_logged_in() || !has_permission('manage_players')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get players with team and ward information
$players = $db->fetchAll("
    SELECT p.*, t.name AS team_name, w.name AS ward_name 
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
    <title>Players Management - Governor Wavinya Cup</title>
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
        <!-- Sidebar -->
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
                    <a href="../players/register.php" class="btn btn-primary" onclick="console.log('Add New Player clicked')">
                        <i class="fas fa-plus me-2"></i>Add New Player
                    </a>
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
                                <a href="../players/register.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Register First Player
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Player Name</th>
                                            <th>Team</th>
                                            <th>Ward</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($players as $player): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></strong><br>
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
                                                <td><?php echo format_date($player['created_at']); ?></td>
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
<script>
    // Debug script to ensure links are working
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Players page loaded');

        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                console.log('Button clicked:', this.textContent.trim());
                if (this.href) console.log('Button href:', this.href);
            });
        });
    });
</script>
</body>
</html>
