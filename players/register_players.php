<?php
// Ensure session and helpers are loaded first
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

// Check if user is logged in and has permission
if (!has_permission('manage_players')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Get team_id from the URL or POST request
$team_id = $_REQUEST['team_id'] ?? null;

if (!is_numeric($team_id) || $team_id <= 0) {
    redirect('teams.php');
}
$team_id = (int)$team_id;

$max_players = 22;

// Get team details to display
$team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);

if (!$team) {
    redirect('teams.php');
}

$team_name = $team['name'];

// Handle player registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check current player count *before* processing to prevent overflow
    $player_count_result = $db->query("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
    $current_player_count = $player_count_result ? (int)$player_count_result->fetchColumn() : 0;

    if ($current_player_count >= $max_players) {
        $error = 'This team has reached the maximum of ' . $max_players . ' players.';
        goto display_form;
    }
    
    // Retrieve and sanitize all required fields from the form
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
    $consent = isset($_POST['consent']);
    
    // --- Validation Block ---
    if (empty($first_name) || empty($last_name) || empty($gender) || empty($date_of_birth) || empty($position) || empty($id_number)) {
        $error = 'First name, last name, ID number, gender, date of birth, and position are required.';
        goto display_form;
    } elseif (!empty($email) && !validate_email($email)) {
        $error = 'Please enter a valid email address.';
        goto display_form;
    } elseif (!empty($phone) && !validate_phone($phone)) {
        $error = 'Please enter a valid phone number.';
        goto display_form;
    } elseif ($jersey_number < 1 || $jersey_number > 99) {
        $error = 'Jersey number must be between 1 and 99.';
        goto display_form;
    } elseif (!$consent) {
        $error = 'You must confirm that the player has agreed to the privacy policy.';
        goto display_form;
    }
    
    // Require player photo and both ID photos
    if (!isset($_FILES['player_image']) || $_FILES['player_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Player photo is required.';
        goto display_form;
    }
    if (!isset($_FILES['id_photo_front']) || $_FILES['id_photo_front']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'ID photo (front) is required.';
        goto display_form;
    }
    if (!isset($_FILES['id_photo_back']) || $_FILES['id_photo_back']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'ID photo (back) is required.';
        goto display_form;
    }
    
    // Check for duplicate ID number
    $existing_user = $db->fetchRow("SELECT id FROM users WHERE id_number = ?", [$id_number]);
    if ($existing_user) {
        $error = 'A user with this ID number already exists. Please enter a unique ID number.';
        goto display_form;
    }
    
    // Check for duplicate email if provided
    if (!empty($email)) {
        $existing_email = $db->fetchRow("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing_email) {
            $error = 'A user with this email already exists. Please enter a unique email address.';
            goto display_form;
        }
    }
    
    // --- Image Upload & Database Insertion Block ---
    $player_image = null;
    $id_front_image = null;
    $id_back_image = null;
    $uploaded_files = [];

    try {
        // Handle photo uploads (unchanged logic)
        if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['player_image'], 'player', 'photo');
            if (!$upload_result['success']) {
                throw new Exception('Player photo upload failed: ' . $upload_result['error']);
            }
            $player_image = $upload_result['path'];
            $uploaded_files[] = $player_image;
        }

        if (isset($_FILES['id_photo_front']) && $_FILES['id_photo_front']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['id_photo_front'], 'id', 'front');
            if (!$upload_result['success']) {
                throw new Exception('ID Front photo upload failed: ' . $upload_result['error']);
            }
            $id_front_image = $upload_result['path'];
            $uploaded_files[] = $id_front_image;
        }

        if (isset($_FILES['id_photo_back']) && $_FILES['id_photo_back']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['id_photo_back'], 'id', 'back');
            if (!$upload_result['success']) {
                throw new Exception('ID Back photo upload failed: ' . $upload_result['error']);
            }
            $id_back_image = $upload_result['path'];
            $uploaded_files[] = $id_back_image;
        }

        // Start database transaction
        $db->beginTransaction();
        
        try {
            // Create user account for player
            $username = strtolower($first_name . '.' . $last_name . '.' . time());
            $password_hash = password_hash('player123', PASSWORD_DEFAULT);
            $consent_timestamp = date('Y-m-d H:i:s');
            
            $db->query("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number, consent_given_at) 
                VALUES (?, ?, ?, 'player', ?, ?, ?, ?, ?)
            ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number, $consent_timestamp]);
            
            $user_id = $db->lastInsertId();
            
            // Create player profile
            $db->query("
                INSERT INTO players (user_id, team_id, first_name, last_name, gender, position, jersey_number, height_cm, weight_kg, date_of_birth, player_image, preferred_foot, is_active, id_photo_front, id_photo_back, created_by, consent_given_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$user_id, $team_id, $first_name, $last_name, $gender, $position, $jersey_number, $height_cm, $weight_kg, $date_of_birth, $player_image, $preferred_foot, 1, $id_front_image, $id_back_image, $user['id'], $consent_timestamp]);
            
            $player_id = $db->lastInsertId();
            
            // Create player registration
            $db->query("
                INSERT INTO player_registrations (player_id, team_id, season_year, registration_date) 
                VALUES (?, ?, ?, CURDATE())
            ", [$player_id, $team_id, date('Y')]);
            
            // Commit the transaction
            $db->commit();
            
            log_activity($user['id'], 'player_registration', "Registered player: $first_name $last_name for team: $team_name");
            
            // Get the new player count to determine the next player number
            $player_count_result = $db->query("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
            $new_player_count = $player_count_result ? (int)$player_count_result->fetchColumn() : 0;

            if ($new_player_count < $max_players) {
                // Redirect to the next player form with a success message
                redirect('register_players.php?team_id=' . $team_id . '&success=' . urlencode("Player '$first_name $last_name' registered successfully!"));
            } else {
                // Redirect to team view when all players are registered
                redirect('../admin/teams/view_team.php?id=' . $team_id . '&success=' . urlencode('All ' . $max_players . ' players have been registered successfully.'));
            }
            
        } catch (Exception $db_e) {
            // Rollback transaction on database error
            $db->rollBack();
            throw $db_e;
        }

    } catch (Exception $e) {
        // Delete uploaded images if database insert failed
        foreach ($uploaded_files as $file) {
            delete_image($file);
        }
        $error = 'Failed to register player. ' . $e->getMessage();
        goto display_form;
    }
}

display_form:
// Get the current player count at the beginning of the page load.
$player_count_result = $db->query("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);
$current_player_count = $player_count_result ? (int)$player_count_result->fetchColumn() : 0;

// Check for success or error messages in the URL
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Determine which player number to display in the form
$player_to_display = $current_player_count + 1;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Players - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body class="registration-page">
    <div class="container">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
            <h4 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h4>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="registration-card">
                    <div class="registration-card-header text-center">
                        <h2 class="mb-1"><i class="fas fa-users me-2"></i>Register <?php echo htmlspecialchars($team_name); ?> Players</h2>
                        <p class="mb-0 text-light op-7">Register player <?php echo ($player_to_display); ?> of <?php echo $max_players; ?></p>
                    </div>
                    <div class="registration-card-body">

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
                    <form method="POST" action="register_players.php?team_id=<?php echo htmlspecialchars($team_id); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id); ?>">
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
                            <label for="player_image" class="form-label"><i class="fas fa-camera me-2"></i>Player Photo *</label>
                            <input type="file" class="form-control" id="player_image" name="player_image" accept="image/*" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="id_photo_front" class="form-label"><i class="fas fa-id-card me-2"></i>ID Photo (Front) *</label>
                                <input type="file" class="form-control" id="id_photo_front" name="id_photo_front" accept="image/*" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="id_photo_back" class="form-label"><i class="fas fa-id-card me-2"></i>ID Photo (Back) *</label>
                                <input type="file" class="form-control" id="id_photo_back" name="id_photo_back" accept="image/*" required>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                            <label class="form-check-label" for="consent">
                                I confirm that the player has agreed to the <a href="../legal/privacy_policy.php" target="_blank">Privacy Policy</a> and consents to the processing of their personal data.
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-register">
                                <i class="fas fa-user-plus me-2"></i>Register Player <?php echo ($player_to_display); ?>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info text-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>This team has reached the maximum of <?php echo $max_players; ?> players.
                        </div>
                        <a href="../admin/teams/view_team.php?id=<?php echo htmlspecialchars($team_id); ?>" class="btn btn-secondary w-100 mt-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Team Details
                        </a>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>