<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Check if user is logged in and has captain role
if (!is_logged_in() || !has_role('captain')) {
    redirect(app_base_url() . '/auth/login.php');
}

$user = get_logged_in_user();
$db = db();

$captain_team_id = $_SESSION['user_team_id'] ?? null;
$team = null;
$captain_player_profile = null;
$players = [];
$activities = [];

// Fetch captain's team details if available
if ($captain_team_id) {
    // Fetch team details
    $team = $db->fetchRow("
        SELECT t.*, w.name as ward_name, sc.name as sub_county_name
        FROM teams t
        LEFT JOIN wards w ON t.ward_id = w.id
        LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
        WHERE t.id = ? AND t.captain_user_id = ?
    ", [$captain_team_id, $user['id']]);

    if ($team) {
        // Fetch captain's player profile
        $captain_player_profile = $db->fetchRow("
            SELECT * FROM players 
            WHERE user_id = ? AND team_id = ? AND is_active = 1
        ", [$user['id'], $captain_team_id]);

        // Get all team players (including the captain if they have a player profile)
        $players = $db->fetchAll("
            SELECT p.*, u.first_name, u.last_name, u.email, u.phone
            FROM players p
            JOIN users u ON p.user_id = u.id
            WHERE p.team_id = ? AND p.is_active = 1
            ORDER BY p.position, u.first_name
        ", [$captain_team_id]);

        // Get recent activities for this captain
        $activities = $db->fetchAll("
            SELECT * FROM activity_log 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$user['id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Captain Dashboard'; include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body class="main-content">
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="dashboard-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0"><i class="fas fa-crown me-2"></i>Captain Dashboard</h1>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?php echo app_base_url(); ?>/auth/logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>

                    <?php if ($team): ?>
                        <div class="profile-card">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <?php if ($captain_player_profile['player_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($captain_player_profile['player_image']); ?>" 
                                             alt="Captain Photo" class="captain-photo mb-3">
                                    <?php else: ?>
                                        <div class="captain-photo mb-3 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-crown fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h2 class="mb-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                                    <p class="mb-1"><i class="fas fa-crown me-2"></i>Role: Team Captain</p>
                                    <?php if ($captain_player_profile): ?>
                                        <p class="mb-1"><i class="fas fa-tshirt me-2"></i>Position: <?php echo htmlspecialchars($captain_player_profile['position']); ?></p>
                                        <p class="mb-1"><i class="fas fa-hashtag me-2"></i>Jersey: #<?php echo htmlspecialchars($captain_player_profile['jersey_number']); ?></p>
                                    <?php else: ?>
                                        <p class="mb-1"><i class="fas fa-tshirt me-2"></i>Position: N/A (Not registered as a player)</p>
                                    <?php endif; ?>
                                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p class="mb-0"><i class="fas fa-phone me-2"></i>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <?php if ($team['team_logo']): ?>
                                        <img src="../<?php echo htmlspecialchars($team['team_logo']); ?>" 
                                             alt="Team Logo" class="team-logo mb-2">
                                    <?php else: ?>
                                        <div class="team-logo mb-2 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-shield-alt fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($team['name'] ?? 'No Team Assigned'); ?></h5>
                                    <small class="text-light"><?php echo htmlspecialchars($team['ward_name'] ?? ''); ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card primary text-center">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1"><?php echo count($players); ?></h4>
                                    <p class="text-muted mb-0">Team Players</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card warning text-center">
                                    <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1">Active</h4>
                                    <p class="text-muted mb-0">Team Status</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card success text-center">
                                    <i class="fas fa-map-marker-alt fa-2x text-success mb-2"></i>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($team['sub_county_name'] ?? 'N/A'); ?></h4>
                                    <p class="text-muted mb-0">Sub County</p>
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

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-users me-2"></i>Team Roster (<?php echo count($players); ?>)
                                        </h5>
                                        <a href="add_players.php?team_id=<?php echo $captain_team_id; ?>" class="btn btn-sm btn-custom">
                                            <i class="fas fa-user-plus me-2"></i>Add Player
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php if (!empty($players)): ?>
                                                <?php foreach ($players as $player): ?>
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="player-card">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-shrink-0">
                                                                    <?php if ($player['player_image']): ?>
                                                                        <img src="../<?php echo htmlspecialchars($player['player_image']); ?>" 
                                                                             alt="Player Photo" class="rounded-circle player-avatar">
                                                                    <?php else: ?>
                                                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center player-avatar-placeholder">
                                                                            <i class="fas fa-user text-muted"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="flex-grow-1 ms-3">
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h6>
                                                                    <span class="badge bg-primary position-badge"><?php echo htmlspecialchars($player['position']); ?></span>
                                                                    <span class="badge bg-secondary position-badge">#<?php echo htmlspecialchars($player['jersey_number']); ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="col-12 text-center py-5">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <h5>No Players Registered Yet</h5>
                                                    <p class="text-muted">Register your first player to get started!</p>
                                                    <a href="add_players.php?team_id=<?php echo $captain_team_id; ?>" class="btn btn-custom mt-3">
                                                        <i class="fas fa-user-plus me-2"></i>Add First Player
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-bell me-2"></i>Recent Activities
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($activities)): ?>
                                            <?php foreach ($activities as $activity): ?>
                                                <div class="activity-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                        </div>
                                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">No recent activity.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h3>Team Not Found</h3>
                            <p class="text-muted">Your team profile could not be found. Please contact your administrator.</p>
                            <a href="../auth/logout.php" class="btn btn-custom">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>