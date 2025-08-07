<?php
require_once '../config/config.php';

// Check if user is logged in and has player role
if (!is_logged_in() || !has_role('player')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get player profile with team information
$player = $db->fetchRow("
    SELECT p.*, t.name as team_name, t.team_logo, t.team_photo, w.name as ward_name, sc.name as sub_county_name
    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE p.user_id = ? AND p.is_active = 1
", [$user['id']]);

// Get player registration history
$registrations = $db->fetchAll("
    SELECT pr.*, t.name as team_name, t.team_logo
    FROM player_registrations pr
    JOIN teams t ON pr.team_id = t.id
    WHERE pr.player_id = ?
    ORDER BY pr.registration_date DESC
", [$player['id'] ?? 0]);

// Get recent activities
$activities = $db->fetchAll("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Dashboard - Machakos County Team Registration System</title>
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
        .player-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
        }
        .activity-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }
        .registration-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
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
                                <i class="fas fa-user me-2"></i>Player Dashboard
                            </h1>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="../auth/logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>

                    <?php if ($player): ?>
                        <!-- Profile Section -->
                        <div class="profile-card">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <?php if ($player['player_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($player['player_image']); ?>" 
                                             alt="Player Photo" class="player-photo mb-3">
                                    <?php else: ?>
                                        <div class="player-photo mb-3 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h2 class="mb-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                                    <p class="mb-1"><i class="fas fa-tshirt me-2"></i>Position: <?php echo htmlspecialchars($player['position']); ?></p>
                                    <p class="mb-1"><i class="fas fa-hashtag me-2"></i>Jersey: #<?php echo htmlspecialchars($player['jersey_number']); ?></p>
                                    <p class="mb-1"><i class="fas fa-ruler-vertical me-2"></i>Height: <?php echo htmlspecialchars($player['height_cm']); ?> cm</p>
                                    <p class="mb-1"><i class="fas fa-weight me-2"></i>Weight: <?php echo htmlspecialchars($player['weight_kg']); ?> kg</p>
                                    <p class="mb-0"><i class="fas fa-birthday-cake me-2"></i>DOB: <?php echo date('F j, Y', strtotime($player['date_of_birth'])); ?></p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <?php if ($player['team_logo']): ?>
                                        <img src="../<?php echo htmlspecialchars($player['team_logo']); ?>" 
                                             alt="Team Logo" class="team-logo mb-2">
                                    <?php else: ?>
                                        <div class="team-logo mb-2 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-shield-alt fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($player['team_name'] ?? 'No Team Assigned'); ?></h5>
                                    <small class="text-light"><?php echo htmlspecialchars($player['ward_name'] ?? ''); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1"><?php echo count($registrations); ?></h4>
                                    <p class="text-muted mb-0">Registrations</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo $player['team_id'] ? 'Active' : 'Inactive'; ?></h4>
                                    <p class="text-muted mb-0">Team Status</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <i class="fas fa-map-marker-alt fa-2x text-success mb-2"></i>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($player['sub_county_name'] ?? 'N/A'); ?></h4>
                                    <p class="text-muted mb-0">Sub County</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                    <h4 class="mb-1"><?php echo date('Y'); ?></h4>
                                    <p class="text-muted mb-0">Season</p>
                                </div>
                            </div>
                        </div>

                        <!-- Registration History -->
                        <?php if (!empty($registrations)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-history me-2"></i>Registration History
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($registrations as $reg): ?>
                                                <div class="registration-card">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-2">
                                                            <?php if ($reg['team_logo']): ?>
                                                                <img src="../<?php echo htmlspecialchars($reg['team_logo']); ?>" 
                                                                     alt="Team Logo" class="team-logo">
                                                            <?php else: ?>
                                                                <div class="team-logo d-flex align-items-center justify-content-center bg-light">
                                                                    <i class="fas fa-shield-alt text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($reg['team_name']); ?></h6>
                                                            <p class="text-muted mb-0">Season: <?php echo htmlspecialchars($reg['season_year']); ?></p>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <span class="badge bg-success">Registered</span>
                                                            <br>
                                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($reg['registration_date'])); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
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
                        <!-- No Player Profile Found -->
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h3>Profile Not Found</h3>
                            <p class="text-muted">Your player profile could not be found. Please contact your administrator.</p>
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