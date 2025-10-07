<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Check if user is logged in and has captain role
if (!is_logged_in() || !has_role('captain')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();
$error = '';

$captain_team_id = $_SESSION['user_team_id'] ?? null;
$team = null;
$players = [];

if (!$captain_team_id) {
    $error = 'You do not have a team assigned. Please contact an administrator.';
} else {
    // Fetch team details
    $team = $db->fetchRow("
        SELECT t.*, w.name as ward_name, sc.name as sub_county_name
        FROM teams t
        LEFT JOIN wards w ON t.ward_id = w.id
        LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
        WHERE t.id = ? AND t.captain_user_id = ?
    ", [$captain_team_id, $user['id']]);

    if (!$team) {
        $error = 'Team not found or you are not the assigned captain.';
    } else {
        // Get all team players
        $players = $db->fetchAll("
            SELECT p.*, u.first_name, u.last_name, u.email, u.phone
            FROM players p
            JOIN users u ON p.user_id = u.id
            WHERE p.team_id = ? AND p.is_active = 1
            ORDER BY p.position, u.first_name
        ", [$captain_team_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'View Team'; include dirname(__DIR__) . '/includes/head.php'; ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-fluid {
            padding-top: 2rem;
        }
        .team-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .team-logo {
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
        .position-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="team-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <a href="../auth/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($team): ?>
                        <div class="text-center mb-5">
                            <?php if ($team['team_logo']): ?>
                                <img src="../<?php echo htmlspecialchars($team['team_logo']); ?>" 
                                     alt="Team Logo" class="team-logo mb-3">
                            <?php else: ?>
                                <div class="team-logo mb-3 d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-shield-alt fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <h2 class="mb-1"><?php echo htmlspecialchars($team['name']); ?></h2>
                            <p class="text-muted"><?php echo htmlspecialchars($team['ward_name'] ?? 'N/A') . ' Ward, ' . htmlspecialchars($team['sub_county_name'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-users me-2"></i>Team Roster (<?php echo count($players); ?>)
                            </h4>
                            <a href="add_players.php" class="btn btn-custom">
                                <i class="fas fa-user-plus me-2"></i>Add Player
                            </a>
                        </div>
                        
                        <?php if (!empty($players)): ?>
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>No players have been registered for this team yet.
                                <br><a href="add_players.php" class="btn btn-custom mt-3">
                                    <i class="fas fa-user-plus me-2"></i>Register Players
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>