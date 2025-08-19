<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user has admin permissions
if (!is_logged_in() || !has_permission('manage_players')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

/**
 * A helper function to correctly generate image URLs.
 * This function uses the full path stored in the database, with an adjustment for the server's directory structure.
 *
 * @param string|null $db_path The image path from the database (e.g., 'uploads/players/player_photo.jpg').
 * @return string The full URL to the image, or a placeholder if not found.
 */
function get_image_url($db_path) {
    if (empty($db_path)) {
        // Return a placeholder image if no filename is provided
        return 'https://via.placeholder.com/150';
    }
    
    // Construct the full path using the base URL and the path from the database.
    $base_url = 'http://localhost:8000/';
    
    // The database path starts with 'uploads/players/', but your file structure is '/players/uploads/players/'.
    // We need to prepend 'players/' to the database path to create the correct URL.
    $full_path = 'players/' . $db_path;
    $path = $base_url . $full_path;
    
    return $path;
}

// Check if a player ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect back to the players list if no ID is found
    redirect('players.php');
}

$player_id = (int)$_GET['id'];

// Get player details from the database using fetchAll and a WHERE clause
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, t.name AS team_name, w.name AS ward_name
    FROM players p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    WHERE p.id = ?
", [$player_id]);

// If no player is found with that ID, redirect back
if (empty($players)) {
    redirect('players.php');
}

// Get the first (and only) player from the result
$player = $players[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?> - Player Details</title>
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
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .id-photo {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
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
                    <a class="nav-link active" href="players.php">
                        <i class="fas fa-user me-2"></i>Players
                    </a>
                    <a class="nav-link" href="teams.php">
                        <i class="fas fa-users me-2"></i>Teams
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
                        <h2><i class="fas fa-user me-2"></i>Player Details</h2>
                        <p class="text-muted">Detailed information for <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></p>
                    </div>
                    <a href="players.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Players
                    </a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="<?php echo get_image_url($player['player_image']); ?>" alt="Player Photo" class="profile-img">
                                <h4 class="mt-3"><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($player['email']); ?></p>
                                <span class="badge bg-<?php echo $player['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $player['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div class="col-md-8">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Team:</strong> 
                                        <?php if ($player['team_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($player['team_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No Team</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="list-group-item"><strong>Ward:</strong> <?php echo htmlspecialchars($player['ward_name'] ?? 'N/A'); ?></li>
                                    <li class="list-group-item"><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($player['gender'] ?? 'N/A')); ?></li>
                                    <li class="list-group-item"><strong>Date of Birth:</strong> <?php echo htmlspecialchars(format_date($player['date_of_birth'] ?? 'N/A', 'Y-m-d')); ?></li>
                                    <li class="list-group-item"><strong>Position:</strong> <?php echo htmlspecialchars($player['position'] ?? 'N/A'); ?></li>
                                    <li class="list-group-item"><strong>Registration Date:</strong> <?php echo htmlspecialchars(format_date($player['created_at'])); ?></li>
                                </ul>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mt-4 mb-3">ID Photos</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text-center"><strong>Front</strong></p>
                                <img src="<?php echo get_image_url($player['id_photo_front']); ?>" alt="ID Photo Front" class="id-photo">
                            </div>
                            <div class="col-md-6">
                                <p class="text-center"><strong>Back</strong></p>
                                <img src="<?php echo get_image_url($player['id_photo_back']); ?>" alt="ID Photo Back" class="id-photo">
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="edit_player.php?id=<?php echo htmlspecialchars($player['id']); ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Edit Player
                            </a>
                            <a href="delete_player.php?id=<?php echo htmlspecialchars($player['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this player?');">
                                <i class="fas fa-trash me-2"></i>Delete Player
                            </a>
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