<?php
require_once '../config/config.php';

// Check if user is logged in and has sub_county_admin role
if (!is_logged_in() || !has_role('sub_county_admin')) {
    redirect(app_base_url() . '/auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get sub-county information
$sub_county = $db->fetchRow("
    SELECT sc.* 
    FROM sub_counties sc
    WHERE sc.name LIKE ? OR sc.code LIKE ?
    LIMIT 1
", ['%' . $user['first_name'] . '%', '%' . $user['last_name'] . '%']);

// Get teams in this sub-county
$teams = $db->fetchAll("
    SELECT t.*, w.name as ward_name, COUNT(p.id) as player_count
    FROM teams t
    JOIN wards w ON t.ward_id = w.id
    LEFT JOIN players p ON t.id = p.team_id AND p.is_active = 1
    WHERE w.sub_county_id = ? AND t.status = 'active'
    GROUP BY t.id
    ORDER BY t.name
", [$sub_county['id'] ?? 0]);

// Get total players in sub-county
$total_players = $db->fetchRow("
    SELECT COUNT(p.id) as count
    FROM players p
    JOIN teams t ON p.team_id = t.id
    JOIN wards w ON t.ward_id = w.id
    WHERE w.sub_county_id = ? AND p.is_active = 1
", [$sub_county['id'] ?? 0])['count'] ?? 0;

// Get total coaches in sub-county
$total_coaches = $db->fetchRow("
    SELECT COUNT(c.id) as count
    FROM coaches c
    JOIN wards w ON c.ward_id = w.id
    WHERE w.sub_county_id = ? AND c.is_active = 1
", [$sub_county['id'] ?? 0])['count'] ?? 0;

// Get recent activities
$activities = $db->fetchAll("
    SELECT al.*, u.first_name, u.last_name
    FROM activity_log al
    JOIN users u ON al.user_id = u.id
    WHERE al.action IN ('team_registration', 'player_registration', 'coach_registration')
    ORDER BY al.created_at DESC
    LIMIT 10
", []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sub-County Admin Dashboard - Machakos County Team Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body class="main-content">
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="dashboard-container p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">
                                <i class="fas fa-map me-2"></i>Sub-County Admin Dashboard
                            </h1>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="../auth/logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>

                    <!-- Profile Section -->
                    <div class="profile-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                                <p class="mb-1"><i class="fas fa-user-shield me-2"></i>Role: Sub-County Administrator</p>
                                <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>Sub-County: <?php echo htmlspecialchars($sub_county['name'] ?? 'Not Assigned'); ?></p>
                                <p class="mb-1"><i class="fas fa-envelope me-2"></i>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                                <p class="mb-0"><i class="fas fa-phone me-2"></i>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-map fa-4x text-light mb-3"></i>
                                <h5 class="mb-1"><?php echo htmlspecialchars($sub_county['name'] ?? 'Not Assigned'); ?></h5>
                                <small class="text-light">Sub-County</small>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card primary text-center">
                                <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
                                <h4 class="mb-1"><?php echo count($teams); ?></h4>
                                <p class="text-muted mb-0">Active Teams</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success text-center">
                                <i class="fas fa-users fa-2x text-success mb-2"></i>
                                <h4 class="mb-1"><?php echo $total_players; ?></h4>
                                <p class="text-muted mb-0">Total Players</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning text-center">
                                <i class="fas fa-user-tie fa-2x text-warning mb-2"></i>
                                <h4 class="mb-1"><?php echo $total_coaches; ?></h4>
                                <p class="text-muted mb-0">Total Coaches</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info text-center">
                                <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                <h4 class="mb-1"><?php echo date('Y'); ?></h4>
                                <p class="text-muted mb-0">Season</p>
                            </div>
                        </div>
                    </div>

                    <!-- Teams in Sub-County -->
                    <?php if (!empty($teams)): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-shield-alt me-2"></i>Teams in <?php echo htmlspecialchars($sub_county['name'] ?? 'Sub-County'); ?> (<?php echo count($teams); ?>)
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($teams as $team): ?>
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="team-card">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0">
                                                                <?php if ($team['team_logo']): ?>
                                                                    <img src="../<?php echo htmlspecialchars($team['team_logo']); ?>" 
                                                                         alt="Team Logo" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                                        <i class="fas fa-shield-alt text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($team['name']); ?></h6>
                                                                <p class="text-muted mb-1"><?php echo htmlspecialchars($team['ward_name']); ?></p>
                                                                <span class="badge bg-primary"><?php echo htmlspecialchars($team['player_count']); ?> Players</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                                        <h5>No Teams Found</h5>
                                        <p class="text-muted">No teams have been registered in this sub-county yet.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent Activities -->
                    <?php if (!empty($activities)): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-bell me-2"></i>Recent System Activities
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($activities as $activity): ?>
                                            <div class="activity-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                                        <p class="text-muted mb-0">
                                                            <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?> - 
                                                            <?php echo htmlspecialchars($activity['description']); ?>
                                                        </p>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
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