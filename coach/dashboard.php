<?php
require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/image_upload.php';

// Check if user is logged in and has coach role
if (!is_logged_in() || !has_role('coach')) {
    redirect(app_base_url() . '/auth/coach_login.php');
}

$user = get_logged_in_user();
$db = db();

// Get coach profile with team information
$coach = $db->fetchRow("
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone, t.name as team_name, t.id as team_id, t.team_code, w.name as ward_name, sc.name as sub_county_name
    FROM coaches c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE c.user_id = ?
", [$user['id']]);

// Get team players
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id as user_id
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
        COUNT(CASE WHEN p.position = 'goalkeeper' THEN 1 END) as goalkeepers,
        COUNT(CASE WHEN p.position = 'defender' THEN 1 END) as defenders,
        COUNT(CASE WHEN p.position = 'midfielder' THEN 1 END) as midfielders,
        COUNT(CASE WHEN p.position = 'forward' THEN 1 END) as forwards
    FROM players p
    WHERE p.team_id = ? AND p.is_active = 1
", [$coach['team_id'] ?? 0]);
?>
<?php
// Fetch player counts for stats
$player_count = count($players);
$approved_players_count = 0;
$pending_players_count = 0;
foreach ($players as $player) {
    // Since players table doesn't have is_approved column, all active players are considered approved
    $approved_players_count++;
}

$team_name = $coach['team_name'] ?? 'N/A';
$ward_name = $coach['ward_name'] ?? 'N/A';
$sub_county_name = $coach['sub_county_name'] ?? 'N/A';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Coach Dashboard'; include dirname(__DIR__) . '/includes/head.php'; ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-header {
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        .profile-card, .team-card, .players-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        /* Improve visibility of card headings and text on white cards */
        .profile-card h4,
        .team-card h4,
        .players-card h4,
        .profile-card, .team-card, .players-card {
            color: #212529; /* Bootstrap body text color */
        }
        .profile-card h4 i,
        .team-card h4 i,
        .players-card h4 i {
            color: #667eea;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        .list-group-item {
            background: transparent;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="dashboard-header">
            <img src="../assets/images/logo.png" alt="Logo" style="width: 100px;" class="mb-3">
            <h1><?php echo APP_NAME; ?></h1>
            <p class="lead">Welcome, Coach <?php echo htmlspecialchars($coach['first_name']); ?>!</p>
        </div>

        <!-- Stats -->
        <div class="row mb-4 text-center">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <h3><?php echo $player_count; ?> / 22</h3>
                    <p class="text-muted">Registered Players</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <h3><?php echo $approved_players_count; ?></h3>
                    <p class="text-muted">Approved Players</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <h3><?php echo $pending_players_count; ?></h3>
                    <p class="text-muted">Pending Approval</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Left Column: Profile & Team -->
            <div class="col-lg-4 mb-4">
                <div class="profile-card mb-4">
                    <h4><i class="fas fa-user-circle me-2"></i>Your Profile</h4>
                    <?php 
                        $coach_img = get_image_url($coach['coach_image'] ?? '', 'coach');
                    ?>
                    <div class="text-center mb-3">
                        <img src="../<?php echo htmlspecialchars($coach_img); ?>?v=<?php echo time(); ?>" alt="Coach Photo" class="img-fluid rounded" style="max-height: 220px; object-fit: cover;">
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Name:</strong> <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></li>
                        <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($coach['email']); ?></li>
                        <li class="list-group-item"><strong>Phone:</strong> <?php echo htmlspecialchars($coach['phone']); ?></li>
                    </ul>
                </div>
                <div class="team-card">
                    <h4><i class="fas fa-shield-alt me-2"></i>Your Team</h4>
                    <?php if ($team_name !== 'N/A'): ?>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Team Name:</strong> <?php echo htmlspecialchars($team_name); ?></li>
                            <li class="list-group-item"><strong>Ward:</strong> <?php echo htmlspecialchars($ward_name); ?></li>
                            <li class="list-group-item"><strong>Sub-County:</strong> <?php echo htmlspecialchars($sub_county_name); ?></li>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">You are not yet assigned to a team. Please wait for the admin to assign you.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Players & Actions -->
            <div class="col-lg-8 mb-4">
                <div class="players-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-clipboard-list me-2"></i>Team Roster</h4>
                        <a href="manage_team.php?team_id=<?php echo (int)($coach['team_id'] ?? 0); ?>" class="btn btn-custom">
                            <i class="fas fa-tools me-2"></i>Manage Team
                        </a>
                    </div>
                    <?php if (empty($players)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p>No players have been added to your team yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Jersey #</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($players, 0, 5) as $player): // Show first 5 players ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($player['position'])); ?></span></td>
                                            <td><?php echo htmlspecialchars($player['jersey_number']); ?></td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($players) > 5): ?>
                            <p class="text-muted text-center mt-2">...and <?php echo count($players) - 5; ?> more.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="text-center">
            <a href="<?php echo app_base_url(); ?>/auth/logout.php?to=coach" class="btn btn-light">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 