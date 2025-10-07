<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Authenticate and authorize viewer
if (!is_logged_in() || !has_permission('view_dashboard')) {
    redirect(app_base_url() . '/auth/login.php');
}

$user = get_logged_in_user();
$page_title = 'Viewer Dashboard';

// Fetch statistics
$db = db();
$total_teams = $db->fetchColumn("SELECT COUNT(*) FROM teams WHERE status = 'active'") ?? 0;
$total_players = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE is_active = 1") ?? 0;
$total_coaches = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'coach'") ?? 0;
$pending_coaches = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'coach' AND approval_status = 'pending'") ?? 0;

// Get recent teams
$recent_teams = $db->fetchAll("
    SELECT t.name, w.name as ward_name, sc.name as sub_county_name 
    FROM teams t 
    JOIN wards w ON t.ward_id = w.id 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    WHERE t.status = 'active' 
    ORDER BY t.created_at DESC 
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'sidebar.php'; ?>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                            <p class="text-muted">Viewer Dashboard</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_teams; ?></h3>
                                        <p class="text-muted mb-0">Active Teams</p>
                                    </div>
                                    <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
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
                                    <div class="stat-icon text-success"><i class="fas fa-user"></i></div>
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
                                    <div class="stat-icon text-warning"><i class="fas fa-chalkboard-teacher"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $pending_coaches; ?></h3>
                                        <p class="text-muted mb-0">Pending Coaches</p>
                                    </div>
                                    <div class="stat-icon text-danger"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Quick Links</h5>
                                </div>
                                <div class="card-body">
                                    <a href="view_teams.php" class="btn btn-primary"><i class="fas fa-shield-alt me-2"></i>View All Teams</a>
                                    <a href="view_players.php" class="btn btn-secondary"><i class="fas fa-users me-2"></i>View All Players</a>
                                    <a href="view_coaches.php" class="btn btn-info"><i class="fas fa-chalkboard-teacher me-2"></i>View All Coaches</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Recent Teams</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recent_teams as $team): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($team['ward_name']); ?>, <?php echo htmlspecialchars($team['sub_county_name']); ?></small>
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
</body>
</html>
