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

// Get players with team and ward information
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, t.name AS team_name, w.name AS ward_name 
    FROM players p 
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN teams t ON p.team_id = t.id 
    LEFT JOIN wards w ON t.ward_id = w.id 
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Players Management'; include dirname(__DIR__) . '/includes/head.php'; ?>
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

        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-user me-2"></i>Players Management</h2>
                        <p class="text-muted">Manage all registered players</p>
                    </div>
                    <a href="../players/register.php" class="btn btn-primary" onclick="console.log('Add New Player clicked')">
                        <i class="fas fa-plus me-2"></i>Add New Player
                    </a>
                </div>

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
                                                    <a href="view_player.php?id=<?php echo htmlspecialchars($player['id']); ?>" class="btn btn-sm btn-outline-primary" title="View Player">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_player.php?id=<?php echo htmlspecialchars($player['id']); ?>" class="btn btn-sm btn-outline-warning" title="Edit Player">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete_player.php?id=<?php echo htmlspecialchars($player['id']); ?>" class="btn btn-sm btn-outline-danger" title="Delete Player" onclick="return confirm('Are you sure you want to delete this player?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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