<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

// Check if user is logged in and has captain role
if (!is_logged_in() || !has_role('captain')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Get team_id from the session, not the URL, to prevent captains from manipulating it
$team_id = $_SESSION['user_team_id'] ?? null;

if (!$team_id) {
    // If the captain doesn't have a team ID in their session, they can't add players.
    $error = 'You do not have a team assigned. Please contact an administrator.';
    // A captain should not be able to access this page without a team.
    // We redirect to the dashboard if this happens.
    redirect('dashboard.php?error=' . urlencode($error));
}

// Get team details to display
$team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);

if (!$team) {
    $error = 'Team not found.';
    redirect('dashboard.php?error=' . urlencode($error));
}
$team_name = $team['name'];

// Define max players for a team
$max_players = 22;

// Check current player count for this team
$current_player_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);

// Handle success or error messages from the URL after a redirect
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Determine which player number to display in the form
$player_to_display = $current_player_count + 1;

// Handle player registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the team is already full
    if ($current_player_count >= $max_players) {
        $error = 'This team has reached the maximum of ' . $max_players . ' players.';
    } else {
        // Retrieve and sanitize form data
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $id_number = sanitize_input($_POST['id_number'] ?? '');
        $gender = sanitize_input($_POST['gender'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $position = $_POST['position'] ?? '';
        $jersey_number = (int)($_POST['jersey_number'] ?? 0);
        $height_cm = (int)($_POST['height_cm'] ?? 0);
        $weight_kg = (float)($_POST['weight_kg'] ?? 0);
        $preferred_foot = $_POST['preferred_foot'] ?? 'right';
        $uploaded_files = [];

        // Validation Block
        if (empty($first_name) || empty($last_name) || empty($gender) || empty($date_of_birth) || empty($position) || empty($id_number)) {
            $error = 'First name, last name, ID number, gender, date of birth, and position are required.';
        } elseif (!empty($email) && !validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!empty($phone) && !validate_phone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif ($jersey_number < 1 || $jersey_number > 99) {
            $error = 'Jersey number must be between 1 and 99.';
        }

        // If there's an error, redirect back to the form with the error message
        if (!empty($error)) {
            redirect('add_players.php?error=' . urlencode($error));
            exit();
        }

        try {
            // Handle image uploads
            $player_image = null;
            $id_front_image = null;
            $id_back_image = null;

            if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_image($_FILES['player_image'], 'player', 'photo');
                if (!$upload_result['success']) {
                    throw new Exception('Player photo upload failed: ' . $upload_result['error']);
                }
                $player_image = $upload_result['path'];
                $uploaded_files[] = $player_image;
            }

            if (isset($_FILES['id_photo_front']) && $_FILES['id_photo_front']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_image($_FILES['id_photo_front'], 'id', 'front');
                if (!$upload_result['success']) {
                    throw new Exception('ID Front photo upload failed: ' . $upload_result['error']);
                }
                $id_front_image = $upload_result['path'];
                $uploaded_files[] = $id_front_image;
            }

            if (isset($_FILES['id_photo_back']) && $_FILES['id_photo_back']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_image($_FILES['id_photo_back'], 'id', 'back');
                if (!$upload_result['success']) {
                    throw new Exception('ID Back photo upload failed: ' . $upload_result['error']);
                }
                $id_back_image = $upload_result['path'];
                $uploaded_files[] = $id_back_image;
            }

            // Create user account for player
            $username = strtolower($first_name . '.' . $last_name . '.' . time());
            $password_hash = password_hash('player123', PASSWORD_DEFAULT); // Default password
            
            $db->query("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number) 
                VALUES (?, ?, ?, 'player', ?, ?, ?, ?)
            ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number]);
            
            $user_id = $db->lastInsertId();
            
            // Create player profile
            $db->query("
                INSERT INTO players (user_id, team_id, first_name, last_name, gender, date_of_birth, position, jersey_number, height_cm, weight_kg, preferred_foot, player_image, id_photo_front, id_photo_back, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$user_id, $team_id, $first_name, $last_name, $gender, $date_of_birth, $position, $jersey_number, $height_cm, $weight_kg, $preferred_foot, $player_image, $id_front_image, $id_back_image, $user['id']]);
            
            $player_id = $db->lastInsertId();
            
            // Create player registration record
            $db->query("
                INSERT INTO player_registrations (player_id, team_id, season_year, registration_date) 
                VALUES (?, ?, ?, CURDATE())
            ", [$player_id, $team_id, date('Y')]);
            
            log_activity($user['id'], 'player_registration', "Captain {$user['first_name']} registered player: $first_name $last_name for team: $team_name");
            
            $success = "Player '$first_name $last_name' registered successfully!";
            
            // Redirect to the next player form
            if (($current_player_count + 1) < $max_players) {
                redirect('add_players.php?success=' . urlencode($success));
            } else {
                redirect('dashboard.php?success=' . urlencode('All 22 players have been registered.'));
            }
        } catch (Exception $e) {
            // Delete uploaded images if database insert failed
            foreach ($uploaded_files as $file) {
                delete_image($file);
            }
            error_log("Player registration failed for captain: {$user['id']} - " . $e->getMessage());
            $error = 'Failed to register player. ' . $e->getMessage();
            redirect('add_players.php?error=' . urlencode($error));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Players - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .registration-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="registration-card">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-users me-2"></i>Register <?php echo htmlspecialchars($team_name); ?> Players</h2>
                        <p class="text-muted">Register player <?php echo ($player_to_display); ?> of <?php echo $max_players; ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($current_player_count < $max_players): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label"><i class="fas fa-user me-2"></i>First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label"><i class="fas fa-user me-2"></i>Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><i class="fas fa-phone me-2"></i>Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_number" class="form-label"><i class="fas fa-id-card me-2"></i>ID Number *</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label"><i class="fas fa-venus-mars me-2"></i>Gender *</label>
                                    <select class="form-control" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label"><i class="fas fa-calendar me-2"></i>Date of Birth *</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label"><i class="fas fa-running me-2"></i>Position *</label>
                                    <select class="form-control" id="position" name="position" required>
                                        <option value="">Select Position</option>
                                        <option value="goalkeeper">Goalkeeper</option>
                                        <option value="defender">Defender</option>
                                        <option value="midfielder">Midfielder</option>
                                        <option value="forward">Forward</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="jersey_number" class="form-label"><i class="fas fa-hashtag me-2"></i>Jersey Number</label>
                                    <input type="number" class="form-control" id="jersey_number" name="jersey_number" min="1" max="99">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="height_cm" class="form-label"><i class="fas fa-ruler-vertical me-2"></i>Height (cm)</label>
                                    <input type="number" class="form-control" id="height_cm" name="height_cm" min="100" max="250">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="weight_kg" class="form-label"><i class="fas fa-weight me-2"></i>Weight (kg)</label>
                                    <input type="number" class="form-control" id="weight_kg" name="weight_kg" min="30" max="150" step="0.1">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="preferred_foot" class="form-label"><i class="fas fa-shoe-prints me-2"></i>Preferred Foot</label>
                                <select class="form-control" id="preferred_foot" name="preferred_foot">
                                    <option value="right" selected>Right</option>
                                    <option value="left">Left</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            
                            <hr>

                            <div class="mb-4">
                                <label for="player_image" class="form-label"><i class="fas fa-camera me-2"></i>Player Photo</label>
                                <input type="file" class="form-control" id="player_image" name="player_image" accept="image/*">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="id_photo_front" class="form-label"><i class="fas fa-id-card me-2"></i>ID Photo (Front)</label>
                                    <input type="file" class="form-control" id="id_photo_front" name="id_photo_front" accept="image/*">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="id_photo_back" class="form-label"><i class="fas fa-id-card me-2"></i>ID Photo (Back)</label>
                                    <input type="file" class="form-control" id="id_photo_back" name="id_photo_back" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-register">
                                    <i class="fas fa-user-plus me-2"></i>Register Player <?php echo ($player_to_display); ?>
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info text-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>Your team has reached the maximum of <?php echo $max_players; ?> players.
                        </div>
                        <a href="dashboard.php" class="btn btn-primary w-100 mt-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>