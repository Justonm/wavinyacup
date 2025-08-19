<?php
require_once '../config/config.php';

// Check if user is logged in and has coach role
if (!is_logged_in() || !has_role('coach')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get coach profile with team information
$coach = $db->fetchRow("
    SELECT c.*, t.name as team_name, t.id as team_id, t.team_code, w.name as ward_name, sc.name as sub_county_name
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE c.user_id = ?
", [$user['id']]);

// Get team players
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, u.phone
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1
    ORDER BY p.position, u.first_name
", [$coach['team_id'] ?? 0]);

// Get recent activities
$activities = $db->fetchAll("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
", [$user['id']]);

// Get team statistics
$team_stats = $db->fetchRow("
    SELECT 
        COUNT(p.id) as total_players,
        COUNT(CASE WHEN p.position = 'Goalkeeper' THEN 1 END) as goalkeepers,
        COUNT(CASE WHEN p.position = 'Defender' THEN 1 END) as defenders,
        COUNT(CASE WHEN p.position = 'Midfielder' THEN 1 END) as midfielders,
        COUNT(CASE WHEN p.position = 'Forward' THEN 1 END) as forwards
    FROM players p
    WHERE p.team_id = ? AND p.is_active = 1
", [$coach['team_id'] ?? 0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Dashboard - Machakos County Team Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .team-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
        }
        .coach-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
        }
        .player-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .player-card:hover {
            transform: translateY(-2px);
        }
        .activity-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        .position-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="dashboard-container p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">
                                <i class="fas fa-user-tie me-2"></i>Coach Dashboard
                            </h1>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</p>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($coach && $coach['team_id']): ?>
                                <a href="manage_team.php" class="btn btn-custom">
                                    <i class="fas fa-users me-2"></i>Manage Team
                                </a>
                            <?php endif; ?>
                            <a href="../auth/logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>

                    <?php if ($coach): ?>
                        <!-- Profile Section -->
                        <div class="profile-card">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <?php if ($coach['coach_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($coach['coach_image']); ?>" 
                                             alt="Coach Photo" class="coach-photo mb-3">
                                    <?php else: ?>
                                        <div class="coach-photo mb-3 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user-tie fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h2 class="mb-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                                    <p class="mb-1"><i class="fas fa-certificate me-2"></i>License: <?php echo htmlspecialchars($coach['license_number']); ?></p>
                                    <p class="mb-1"><i class="fas fa-star me-2"></i>License Type: <?php echo htmlspecialchars($coach['license_type']); ?></p>
                                    <p class="mb-1"><i class="fas fa-clock me-2"></i>Experience: <?php echo htmlspecialchars($coach['experience_years']); ?> years</p>
                                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p class="mb-0"><i class="fas fa-phone me-2"></i>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <?php if ($coach['team_logo']): ?>
                                        <img src="../<?php echo htmlspecialchars($coach['team_logo']); ?>" 
                                             alt="Team Logo" class="team-logo mb-2">
                                    <?php else: ?>
                                        <div class="team-logo mb-2 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-shield-alt fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($coach['team_name'] ?? 'No Team Assigned'); ?></h5>
                                    <small class="text-light"><?php echo htmlspecialchars($coach['ward_name'] ?? ''); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1"><?php echo $team_stats['total_players'] ?? 0; ?></h4>
                                    <p class="text-muted mb-0">Total Players</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <i class="fas fa-hand-paper fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo $team_stats['goalkeepers'] ?? 0; ?></h4>
                                    <p class="text-muted mb-0">Goalkeepers</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                    <h4 class="mb-1"><?php echo $team_stats['defenders'] ?? 0; ?></h4>
                                    <p class="text-muted mb-0">Defenders</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <i class="fas fa-circle fa-2x text-info mb-2"></i>
                                    <h4 class="mb-1"><?php echo $team_stats['midfielders'] ?? 0; ?></h4>
                                    <p class="text-muted mb-0">Midfielders</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <i class="fas fa-bullseye fa-2x text-danger mb-2"></i>
                                    <h4 class="mb-1"><?php echo $team_stats['forwards'] ?? 0; ?></h4>
                                    <p class="text-muted mb-0">Forwards</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo $coach['team_id'] ? 'Active' : 'Inactive'; ?></h4>
                                    <p class="text-muted mb-0">Team Status</p>
                                </div>
                            </div>
                        </div>

                        <!-- Team Players -->
                        <?php if (!empty($players)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-users me-2"></i>Team Players (<?php echo count($players); ?>)
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <?php foreach ($players as $player): ?>
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="player-card">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-shrink-0">
                                                                    <?php if ($player['player_image']): ?>
                                                                        <img src="../<?php echo htmlspecialchars($player['player_image']); ?>" 
                                                                             alt="Player Photo" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                                                    <?php else: ?>
                                                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                            <i class="fas fa-user text-muted"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="flex-grow-1 ms-3">
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h6>
                                                                    <span class="badge bg-primary position-badge"><?php echo htmlspecialchars($player['position']); ?></span>
                                                                    <span class="badge bg-secondary position-badge">#<?php echo htmlspecialchars($player['jersey_number']); ?></span>
                                                                    <br>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($player['email']); ?></small>
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
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5>No Players Assigned</h5>
                                            <p class="text-muted">No players have been assigned to your team yet.</p>
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
                                                <i class="fas fa-bell me-2"></i>Recent Activities
                                            </h5>
                                        </div>
                                        <div class="card-body">
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No Coach Profile Found -->
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h3>Profile Not Found</h3>
                            <p class="text-muted">Your coach profile could not be found. Please contact your administrator.</p>
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