<?php
require_once '../config/config.php';

// Check if user has admin permissions
if (!has_permission('all')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get statistics
$total_teams = $db->fetchRow("SELECT COUNT(*) as count FROM teams WHERE status = 'active'")['count'] ?? 0;
$total_players = $db->fetchRow("SELECT COUNT(*) as count FROM players WHERE is_active = 1")['count'] ?? 0;
$total_coaches = $db->fetchRow("SELECT COUNT(*) as count FROM coaches")['count'] ?? 0;
$pending_registrations = $db->fetchRow("SELECT COUNT(*) as count FROM team_registrations WHERE status = 'pending'")['count'] ?? 0;

// Get recent teams
$recent_teams = $db->fetchAll("
    SELECT t.*, w.name as ward_name, sc.name as sub_county_name 
    FROM teams t 
    JOIN wards w ON t.ward_id = w.id 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    WHERE t.status = 'active' 
    ORDER BY t.created_at DESC 
    LIMIT 5
");

// Get teams by sub-county
$teams_by_sub_county = $db->fetchAll("
    SELECT sc.name as sub_county, COUNT(t.id) as team_count 
    FROM sub_counties sc 
    LEFT JOIN wards w ON sc.id = w.sub_county_id 
    LEFT JOIN teams t ON w.id = t.ward_id AND t.status = 'active' 
    GROUP BY sc.id, sc.name 
    ORDER BY team_count DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #b71c1c); /* Updated gradient */
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
        }
        .stat-card.primary { border-left-color: #0d47a1; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #b71c1c; }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.7;
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
                        <a class="nav-link active" href="dashboard.php">
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
                            <h2>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                            <p class="text-muted">County Administrator Dashboard</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Last login: <?php echo format_datetime($user['updated_at']); ?></small>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_teams; ?></h3>
                                        <p class="text-muted mb-0">Active Teams</p>
                                    </div>
                                    <div class="stat-icon text-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_players; ?></h3>
                                        <p class="text-muted mb-0">Registered Players</p>
                                    </div>
                                    <div class="stat-icon text-success">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_coaches; ?></h3>
                                        <p class="text-muted mb-0">Coaches</p>
                                    </div>
                                    <div class="stat-icon text-warning">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $pending_registrations; ?></h3>
                                        <p class="text-muted mb-0">Pending Approvals</p>
                                    </div>
                                    <div class="stat-icon text-danger">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <a href="../teams/register.php" class="btn btn-primary btn-lg w-100 mb-2">
                                                <i class="fas fa-plus me-2"></i>Add New Team
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="../players/register.php" class="btn btn-success btn-lg w-100 mb-2">
                                                <i class="fas fa-plus me-2"></i>Add New Player
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="../coaches/register.php" class="btn btn-warning btn-lg w-100 mb-2">
                                                <i class="fas fa-plus me-2"></i>Add New Coach
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Teams by Sub-County
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="teamsChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>Recent Teams
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recent_teams as $team): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($team['ward_name']); ?>, 
                                                    <?php echo htmlspecialchars($team['sub_county_name']); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('teamsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($teams_by_sub_county, 'sub_county')); ?>,
                datasets: [{
                    label: 'Number of Teams',
                    data: <?php echo json_encode(array_column($teams_by_sub_county, 'team_count')); ?>,
                    backgroundColor: 'rgba(13, 71, 161, 0.8)',
                    borderColor: 'rgba(13, 71, 161, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
