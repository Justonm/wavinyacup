<?php
// coach/manage_team.php - Coach Team Management Page

require_once '../config/config.php';
require_once '../includes/helpers.php';

// Check if user is logged in and has coach role
if (!is_logged_in() || !has_role('coach')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Get coach profile and team
$coach = $db->fetchRow("
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, t.ward_id, w.name as ward_name
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    WHERE c.user_id = ?
", [$user['id']]);

if (!$coach) {
    redirect('dashboard.php');
}

// Get team players
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1
    ORDER BY p.position, u.first_name
", [$coach['team_id'] ?? 0]);

// Handle player addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_player') {
    if (!$coach['team_id']) {
        $error = 'You must have a team assigned before adding players.';
    } else {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $id_number = sanitize_input($_POST['id_number'] ?? '');
        $position = $_POST['position'] ?? '';
        $jersey_number = (int)($_POST['jersey_number'] ?? 0);
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($position) || empty($date_of_birth)) {
            $error = 'All required fields must be filled.';
        } elseif (!validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!empty($phone) && !validate_phone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (count($players) >= 22) {
            $error = 'Maximum of 22 players allowed per team.';
        } elseif ($jersey_number < 1 || $jersey_number > 99) {
            $error = 'Jersey number must be between 1 and 99.';
        } else {
            // Check if email or jersey number already exists in team
            $existing = $db->fetchRow("
                SELECT p.id FROM players p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.team_id = ? AND (u.email = ? OR p.jersey_number = ?)
            ", [$coach['team_id'], $email, $jersey_number]);
            
            if ($existing) {
                $error = 'A player with this email or jersey number already exists in your team.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Create user account for player
                    $username = strtolower(str_replace(' ', '', $first_name) . '.' . str_replace(' ', '', $last_name) . '.' . time());
                    $password_hash = password_hash('player123', PASSWORD_DEFAULT);
                    
                    $db->query("
                        INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number) 
                        VALUES (?, ?, ?, 'player', ?, ?, ?, ?)
                    ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // Create player profile
                    $db->query("
                        INSERT INTO players (user_id, team_id, position, jersey_number, date_of_birth) 
                        VALUES (?, ?, ?, ?, ?)
                    ", [$user_id, $coach['team_id'], $position, $jersey_number, $date_of_birth]);
                    
                    $db->commit();
                    
                    log_activity($user['id'], 'player_added', "Added player: $first_name $last_name to team");
                    $success = "Player '$first_name $last_name' added successfully!";
                    
                    // Refresh players list
                    $players = $db->fetchAll("
                        SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number
                        FROM players p
                        JOIN users u ON p.user_id = u.id
                        WHERE p.team_id = ? AND p.is_active = 1
                        ORDER BY p.position, u.first_name
                    ", [$coach['team_id']]);
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Failed to add player. Error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle player removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_player') {
    $player_id = (int)($_POST['player_id'] ?? 0);
    
    if ($player_id > 0) {
        try {
            $db->query("UPDATE players SET is_active = 0 WHERE id = ? AND team_id = ?", [$player_id, $coach['team_id']]);
            log_activity($user['id'], 'player_removed', "Removed player from team");
            $success = "Player removed successfully!";
            
            // Refresh players list
            $players = $db->fetchAll("
                SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number
                FROM players p
                JOIN users u ON p.user_id = u.id
                WHERE p.team_id = ? AND p.is_active = 1
                ORDER BY p.position, u.first_name
            ", [$coach['team_id']]);
            
        } catch (Exception $e) {
            $error = 'Failed to remove player. Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .team-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        .player-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .player-card:hover {
            transform: translateY(-3px);
        }
        .position-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
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
                <div class="main-container">
                    <!-- Header -->
                    <div class="team-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="fas fa-users me-3"></i>Manage Team</h2>
                                <p class="mb-0"><?php echo htmlspecialchars($coach['team_name'] ?? 'No Team Assigned'); ?></p>
                            </div>
                            <div>
                                <a href="dashboard.php" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$coach['team_id']): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h3>No Team Assigned</h3>
                                <p class="text-muted">You don't have a team assigned yet. Please wait for admin approval.</p>
                            </div>
                        <?php else: ?>
                            <!-- Team Info -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5><i class="fas fa-info-circle me-2"></i>Team Information</h5>
                                            <p><strong>Team Name:</strong> <?php echo htmlspecialchars($coach['team_name']); ?></p>
                                            <p><strong>Team Code:</strong> <?php echo htmlspecialchars($coach['team_code']); ?></p>
                                            <p><strong>Ward:</strong> <?php echo htmlspecialchars($coach['ward_name']); ?></p>
                                            <p><strong>Players:</strong> <?php echo count($players); ?>/22</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5><i class="fas fa-plus me-2"></i>Quick Actions</h5>
                                            <button type="button" class="btn btn-custom me-2 mb-2" data-bs-toggle="modal" data-bs-target="#addPlayerModal" <?php echo count($players) >= 22 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-user-plus me-2"></i>Add Player
                                            </button>
                                            <?php if (count($players) >= 22): ?>
                                                <small class="text-muted d-block">Maximum players reached (22/22)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Players List -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Players (<?php echo count($players); ?>)</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($players)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5>No Players Added</h5>
                                            <p class="text-muted">Start building your team by adding players.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($players as $player): ?>
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="player-card">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h6>
                                                                <span class="badge bg-primary position-badge"><?php echo htmlspecialchars($player['position']); ?></span>
                                                                <span class="badge bg-secondary position-badge">#<?php echo htmlspecialchars($player['jersey_number']); ?></span>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="action" value="remove_player">
                                                                            <input type="hidden" name="player_id" value="<?php echo $player['id']; ?>">
                                                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to remove this player?')">
                                                                                <i class="fas fa-trash me-2"></i>Remove
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($player['email']); ?><br>
                                                            <?php if ($player['phone']): ?>
                                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($player['phone']); ?><br>
                                                            <?php endif; ?>
                                                            <i class="fas fa-birthday-cake me-1"></i><?php echo date('M j, Y', strtotime($player['date_of_birth'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Player Modal -->
    <div class="modal fade" id="addPlayerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Player</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_player">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+254712345678">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position *</label>
                                    <select class="form-control" id="position" name="position" required>
                                        <option value="">Select Position</option>
                                        <option value="goalkeeper">Goalkeeper</option>
                                        <option value="defender">Defender</option>
                                        <option value="midfielder">Midfielder</option>
                                        <option value="forward">Forward</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jersey_number" class="form-label">Jersey Number *</label>
                                    <input type="number" class="form-control" id="jersey_number" name="jersey_number" min="1" max="99" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">
                            <i class="fas fa-save me-2"></i>Add Player
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
