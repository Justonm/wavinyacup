<?php
// coach/manage_team.php - Coach Team Management Page

require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/image_upload.php'; // Include the image upload helper

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
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, t.ward_id, t.team_photo, w.name as ward_name
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    WHERE c.user_id = ?
", [$user['id']]);

if (!$coach) {
    redirect('dashboard.php');
}

$max_players = 22;

// Get team players
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.id as user_id
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1
    ORDER BY p.id ASC
", [$coach['team_id'] ?? 0]);

// Handle team photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_team_photo') {
    if (!$coach['team_id']) {
        $error = 'You must have a team assigned before uploading a team photo.';
    } else {
        // Handle team photo upload
        if (isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['team_photo'], 'teams', 'team_photo');
            if ($upload_result['success']) {
                // Update team photo in database
                $db->query("UPDATE teams SET team_photo = ? WHERE id = ?", [$upload_result['path'], $coach['team_id']]);
                $success = 'Team photo uploaded successfully!';
                // Refresh coach data to show new photo
                $coach = $db->fetchRow("
                    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, t.ward_id, t.team_photo, w.name as ward_name
                    FROM coaches c
                    LEFT JOIN teams t ON c.team_id = t.id
                    LEFT JOIN wards w ON t.ward_id = w.id
                    WHERE c.user_id = ?
                ", [$user['id']]);
            } else {
                $error = 'Team photo upload failed: ' . $upload_result['error'];
            }
        } else {
            $error = 'Please select a valid image file to upload.';
        }
    }
}

// Handle player addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_player') {
    if (!$coach['team_id']) {
        $error = 'You must have a team assigned before adding players.';
    } else {
        // Retrieve and sanitize all fields
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
        } elseif (!empty($email) && !validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!empty($phone) && !validate_phone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif ($jersey_number < 1 || $jersey_number > 99) {
            $error = 'Jersey number must be between 1 and 99.';
        } elseif (!$consent) {
            $error = 'You must confirm that the player has agreed to the privacy policy.';
        } else {
            // Get fresh player count from database to ensure accuracy
            $current_player_count = $db->fetchColumn("SELECT COUNT(*) FROM players WHERE team_id = ? AND is_active = 1", [$coach['team_id']]);
            if ($current_player_count >= $max_players) {
                $error = 'This team has reached the maximum of ' . $max_players . ' players.';
            }
            
            // Check for duplicate ID number
            if (empty($error)) {
                $existing_user_id = $db->fetchRow("SELECT id FROM users WHERE id_number = ?", [$id_number]);
                if ($existing_user_id) {
                    $error = 'A user with this ID number already exists. Please enter a unique ID number.';
                }
            }

            // Check for duplicate email if provided
            if (!empty($email) && empty($error)) {
                $existing_email = $db->fetchRow("SELECT id FROM users WHERE email = ?", [$email]);
                if ($existing_email) {
                    $error = 'A user with this email already exists. Please enter a unique email address.';
                }
            }
            
            // Check for duplicate jersey number in the team
            if (!empty($jersey_number) && empty($error)) {
                $existing_jersey = $db->fetchRow("SELECT id FROM players WHERE team_id = ? AND jersey_number = ?", [$coach['team_id'], $jersey_number]);
                if ($existing_jersey) {
                    $error = 'This jersey number is already taken by a player on this team.';
                }
            }

            // If no validation errors, proceed with DB and file operations
            if (empty($error)) {
                $player_image = null;
                $id_front_image = null;
                $id_back_image = null;
                $uploaded_files = [];

                try {
                    // Handle photo uploads
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

                        // Insert into users table
                        $db->query("
                            INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number, consent_given_at) 
                            VALUES (?, ?, ?, 'player', ?, ?, ?, ?, ?)
                        ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number, $consent_timestamp]);

                        $user_id = $db->lastInsertId();

                        // Insert into players table (Corrected to only include player-specific fields)
                        $db->query("
                            INSERT INTO players (user_id, team_id, gender, position, jersey_number, height_cm, weight_kg, date_of_birth, player_image, preferred_foot, is_active, id_photo_front, id_photo_back, created_by, consent_given_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ", [
                            $user_id, 
                            $coach['team_id'], 
                            $gender, 
                            $position, 
                            $jersey_number, 
                            $height_cm, 
                            $weight_kg, 
                            $date_of_birth, 
                            $player_image, 
                            $preferred_foot, 
                            1, 
                            $id_front_image, 
                            $id_back_image, 
                            $user['id'], 
                            $consent_timestamp
                        ]);
                        
                        $player_id = $db->lastInsertId();

                        // Create player registration
                        $db->query("
                            INSERT INTO player_registrations (player_id, team_id, season_year, registration_date) 
                            VALUES (?, ?, ?, CURDATE())
                        ", [$player_id, $coach['team_id'], date('Y')]);

                        // Commit the transaction
                        $db->commit();

                        log_activity($user['id'], 'player_registration', "Registered player: $first_name $last_name for team: " . $coach['team_name']);
                        $success = "Player '$first_name $last_name' added successfully!";

                        // Refresh players list
                        $players = $db->fetchAll("
                            SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.id as user_id
                            FROM players p
                            JOIN users u ON p.user_id = u.id
                            WHERE p.team_id = ? AND p.is_active = 1
                            ORDER BY p.position, u.first_name
                        ", [$coach['team_id']]);

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
                    $error = 'Failed to add player. ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle player editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_player') {
    $player_id = (int)($_POST['player_id'] ?? 0);
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
    $player_image_updated = false;
    $id_front_updated = false;
    $id_back_updated = false;

    // Fetch the player's existing data to get user_id and current file paths
    $player_to_edit = $db->fetchRow("SELECT p.*, u.id as user_id FROM players p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.team_id = ?", [$player_id, $coach['team_id']]);

    if (!$player_to_edit) {
        $error = "Player not found or does not belong to your team.";
    } else {
        // Validation
        if (empty($first_name) || empty($last_name) || empty($gender) || empty($date_of_birth) || empty($position) || empty($id_number)) {
            $error = 'First name, last name, ID number, gender, date of birth, and position are required.';
        } elseif (!empty($email) && !validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif ($jersey_number < 1 || $jersey_number > 99) {
            $error = 'Jersey number must be between 1 and 99.';
        } else {
            // Check for duplicate ID and email (excluding the current user)
            $existing_user_id = $db->fetchRow("SELECT id FROM users WHERE id_number = ? AND id != ?", [$id_number, $player_to_edit['user_id']]);
            if ($existing_user_id) {
                $error = 'A user with this ID number already exists.';
            }

            if (!empty($email) && empty($error)) {
                $existing_email = $db->fetchRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $player_to_edit['user_id']]);
                if ($existing_email) {
                    $error = 'A user with this email already exists.';
                }
            }

            // Check for duplicate jersey number (excluding the current player)
            if (!empty($jersey_number) && empty($error)) {
                $existing_jersey = $db->fetchRow("SELECT id FROM players WHERE team_id = ? AND jersey_number = ? AND id != ?", [$coach['team_id'], $jersey_number, $player_id]);
                if ($existing_jersey) {
                    $error = 'This jersey number is already taken by another player on this team.';
                }
            }

            // Require player photo and both ID photos: either existing or newly uploaded
            if (empty($error)) {
                $has_player_image = !empty($player_to_edit['player_image']) || (isset($_FILES['player_image']) && $_FILES['player_image']['error'] === UPLOAD_ERR_OK);
                $has_id_front = !empty($player_to_edit['id_photo_front']) || (isset($_FILES['id_photo_front']) && $_FILES['id_photo_front']['error'] === UPLOAD_ERR_OK);
                $has_id_back = !empty($player_to_edit['id_photo_back']) || (isset($_FILES['id_photo_back']) && $_FILES['id_photo_back']['error'] === UPLOAD_ERR_OK);

                if (!$has_player_image || !$has_id_front || !$has_id_back) {
                    $error = 'Player photo and both ID photos (front and back) are required.';
                }
            }
        }

        if (empty($error)) {
            $uploaded_files = [];
            $old_files = [];

            try {
                $db->beginTransaction();

                // Handle image uploads
                $player_image_path = $player_to_edit['player_image'];
                if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_image($_FILES['player_image'], 'player', 'photo');
                    if (!$upload_result['success']) {
                        throw new Exception('Player photo upload failed: ' . $upload_result['error']);
                    }
                    $old_files[] = $player_image_path;
                    $player_image_path = $upload_result['path'];
                    $player_image_updated = true;
                    $uploaded_files[] = $player_image_path;
                }

                $id_front_path = $player_to_edit['id_photo_front'];
                if (isset($_FILES['id_photo_front']) && $_FILES['id_photo_front']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_image($_FILES['id_photo_front'], 'id', 'front');
                    if (!$upload_result['success']) {
                        throw new Exception('ID Front photo upload failed: ' . $upload_result['error']);
                    }
                    $old_files[] = $id_front_path;
                    $id_front_path = $upload_result['path'];
                    $id_front_updated = true;
                    $uploaded_files[] = $id_front_path;
                }

                $id_back_path = $player_to_edit['id_photo_back'];
                if (isset($_FILES['id_photo_back']) && $_FILES['id_photo_back']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_image($_FILES['id_photo_back'], 'id', 'back');
                    if (!$upload_result['success']) {
                        throw new Exception('ID Back photo upload failed: ' . $upload_result['error']);
                    }
                    $old_files[] = $id_back_path;
                    $id_back_path = $upload_result['path'];
                    $id_back_updated = true;
                    $uploaded_files[] = $id_back_path;
                }

                // Update users table
                $db->query("
                    UPDATE users
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?
                    WHERE id = ?
                ", [$first_name, $last_name, $email, $phone, $id_number, $player_to_edit['user_id']]);

                // Update players table
                $db->query("
                    UPDATE players
                    SET gender = ?, position = ?, jersey_number = ?, height_cm = ?, weight_kg = ?, date_of_birth = ?, player_image = ?, preferred_foot = ?, id_photo_front = ?, id_photo_back = ?
                    WHERE id = ?
                ", [
                    $gender,
                    $position,
                    $jersey_number,
                    $height_cm,
                    $weight_kg,
                    $date_of_birth,
                    $player_image_path,
                    $preferred_foot,
                    $id_front_path,
                    $id_back_path,
                    $player_id
                ]);

                $db->commit();
                log_activity($user['id'], 'player_update', "Updated player: $first_name $last_name (ID: $player_id)");
                $success = "Player '$first_name $last_name' updated successfully!";

                // Clean up old images after successful update
                foreach ($old_files as $file) {
                    delete_image($file);
                }

                // Refresh players list
                $players = $db->fetchAll("
                    SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.id as user_id
                    FROM players p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.team_id = ? AND p.is_active = 1
                    ORDER BY p.id ASC
                ", [$coach['team_id']]);
                
            } catch (Exception $e) {
                $db->rollBack();
                // If the update fails, delete any newly uploaded files to prevent orphans
                foreach ($uploaded_files as $file) {
                    delete_image($file);
                }
                $error = 'Failed to update player. ' . $e->getMessage();
            }
        }
    }
}


// Handle player removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_player') {
    $player_id = (int)($_POST['player_id'] ?? 0);
    
    if ($player_id > 0) {
        try {
            // Soft delete the player by setting is_active to 0
            $db->query("UPDATE players SET is_active = 0 WHERE id = ? AND team_id = ?", [$player_id, $coach['team_id']]);
            log_activity($user['id'], 'player_removed', "Removed player ID $player_id from team " . $coach['team_name']);
            $success = "Player removed successfully!";
            
            // Refresh players list
            $players = $db->fetchAll("
                SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.id as user_id
                FROM players p
                JOIN users u ON p.user_id = u.id
                WHERE p.team_id = ? AND p.is_active = 1
                ORDER BY p.id ASC
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
    <?php $page_title = 'Manage Team'; include dirname(__DIR__) . '/includes/head.php'; ?>
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
            transition: all 0.3s ease;
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
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5><i class="fas fa-image me-2"></i>Team Photo</h5>
                                            <?php if ($coach['team_photo']): ?>
                                                <img src="../<?php echo htmlspecialchars($coach['team_photo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($coach['team_name']); ?>" 
                                                     style="max-height: 200px; width: 100%; object-fit: cover;"
                                                     class="mb-3">
                                            <?php else: ?>
                                                <div class="text-muted py-4 mb-3">
                                                    <i class="fas fa-image fa-3x mb-2"></i>
                                                    <p>No team photo uploaded</p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Upload Photo Form -->
                                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                                <input type="hidden" name="action" value="upload_team_photo">
                                                <div class="mb-3">
                                                    <input type="file" class="form-control form-control-sm" 
                                                           name="team_photo" accept="image/*" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-upload me-1"></i>Upload Photo
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5><i class="fas fa-info-circle me-2"></i>Team Information</h5>
                                            <p><strong>Team Name:</strong> <?php echo htmlspecialchars($coach['team_name']); ?></p>
                                            <p><strong>Team Code:</strong> <?php echo htmlspecialchars($coach['team_code']); ?></p>
                                            <p><strong>Ward:</strong> <?php echo htmlspecialchars($coach['ward_name']); ?></p>
                                            <p><strong>Players:</strong> <?php echo count($players); ?>/<?php echo $max_players; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5><i class="fas fa-plus me-2"></i>Quick Actions</h5>
                                            <button type="button" class="btn btn-custom me-2 mb-2" data-bs-toggle="modal" data-bs-target="#addPlayerModal" <?php echo count($players) >= $max_players ? 'disabled' : ''; ?>>
                                                <i class="fas fa-user-plus me-2"></i>Add Player
                                            </button>
                                            <?php if (count($players) >= $max_players): ?>
                                                <small class="text-muted d-block">Maximum players reached (<?php echo $max_players; ?>/<?php echo $max_players; ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead style="background: #007bff !important; color: white !important;">
                                                    <tr>
                                                        <th style="background: #007bff !important; color: white !important;">#</th>
                                                        <th style="background: #007bff !important; color: white !important;">Photo</th>
                                                        <th style="background: #007bff !important; color: white !important;">Name</th>
                                                        <th style="background: #007bff !important; color: white !important;">Jersey #</th>
                                                        <th style="background: #007bff !important; color: white !important;">Position</th>
                                                        <th style="background: #007bff !important; color: white !important;">Gender</th>
                                                        <th style="background: #007bff !important; color: white !important;">Age</th>
                                                        <th style="background: #007bff !important; color: white !important;">Contact</th>
                                                        <th style="background: #007bff !important; color: white !important;">ID Number</th>
                                                        <th style="background: #007bff !important; color: white !important;">Height/Weight</th>
                                                        <th style="background: #007bff !important; color: white !important;">Preferred Foot</th>
                                                        <th style="background: #007bff !important; color: white !important;">ID Photos</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($players as $index => $player): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo $index + 1; ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php if ($player['player_image']): ?>
                                                                    <a href="#" class="image-modal-trigger" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="../<?php echo htmlspecialchars($player['player_image']); ?>" data-img-title="Player Photo: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                        <img src="../<?php echo htmlspecialchars($player['player_image']); ?>?v=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>" style="width: 150px; height: auto; border: 1px solid #dee2e6; padding: 0.25rem; border-radius: 0.25rem;">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 150px; min-height: 150px;">
                                                                        <i class="fas fa-user fa-2x text-white"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary">#<?php echo htmlspecialchars($player['jersey_number']); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo htmlspecialchars($player['position']); ?></span>
                                                            </td>
                                                            <td><?php echo ucfirst(htmlspecialchars($player['gender'] ?? 'N/A')); ?></td>
                                                            <td>
                                                                <?php 
                                                                if ($player['date_of_birth']) {
                                                                    $age = date_diff(date_create($player['date_of_birth']), date_create('today'))->y;
                                                                    echo $age . ' years';
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <?php if ($player['email']): ?>
                                                                        <i class="fas fa-envelope me-1"></i><a href="mailto:<?php echo htmlspecialchars($player['email']); ?>"><?php echo htmlspecialchars($player['email']); ?></a><br>
                                                                    <?php endif; ?>
                                                                    <?php if ($player['phone']): ?>
                                                                        <i class="fas fa-phone me-1"></i><a href="tel:<?php echo htmlspecialchars($player['phone']); ?>"><?php echo htmlspecialchars($player['phone']); ?></a>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($player['id_number']); ?></td>
                                                            <td>
                                                                <small>
                                                                    <?php if ($player['height_cm']): ?>
                                                                        <?php echo htmlspecialchars($player['height_cm']); ?>cm<br>
                                                                    <?php endif; ?>
                                                                    <?php if ($player['weight_kg']): ?>
                                                                        <?php echo htmlspecialchars($player['weight_kg']); ?>kg
                                                                    <?php endif; ?>
                                                                </small>
                                                            </td>
                                                            <td><?php echo ucfirst(htmlspecialchars($player['preferred_foot'] ?? 'N/A')); ?></td>
                                                            <td>
                                                                <?php if ($player['id_photo_front'] || $player['id_photo_back']): ?>
                                                                    <div class="d-flex gap-1">
                                                                        <?php if ($player['id_photo_front']): ?>
                                                                            <a href="#" class="image-modal-trigger" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" data-img-title="ID Front: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                                <img src="../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" alt="ID Front" style="width: 150px; height: auto; border: 1px solid #dee2e6; padding: 0.25rem; border-radius: 0.25rem;">
                                                                            </a>
                                                                        <?php endif; ?>
                                                                        <?php if ($player['id_photo_back']): ?>
                                                                            <a href="#" class="image-modal-trigger" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" data-img-title="ID Back: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                                <img src="../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" alt="ID Back" style="width: 150px; height: auto; border: 1px solid #dee2e6; padding: 0.25rem; border-radius: 0.25rem;">
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <small class="text-muted">No ID photos</small>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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

    <div class="modal fade" id="addPlayerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Player</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_player">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_number" class="form-label">ID Number *</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
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

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="jersey_number" class="form-label">Jersey Number *</label>
                                <input type="number" class="form-control" id="jersey_number" name="jersey_number" min="1" max="99" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="height_cm" class="form-label">Height (cm)</label>
                                <input type="number" class="form-control" id="height_cm" name="height_cm" min="100" max="250">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="weight_kg" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="weight_kg" name="weight_kg" min="30" max="150" step="0.1">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="preferred_foot" class="form-label">Preferred Foot</label>
                            <select class="form-control" id="preferred_foot" name="preferred_foot">
                                <option value="right" selected>Right</option>
                                <option value="left">Left</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        
                        <hr>

                        <div class="mb-4">
                            <label for="player_image" class="form-label">Player Photo *</label>
                            <input type="file" class="form-control" id="player_image" name="player_image" accept="image/*" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="id_photo_front" class="form-label">ID Photo (Front) *</label>
                                <input type="file" class="form-control" id="id_photo_front" name="id_photo_front" accept="image/*" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="id_photo_back" class="form-label">ID Photo (Back) *</label>
                                <input type="file" class="form-control" id="id_photo_back" name="id_photo_back" accept="image/*" required>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                            <label class="form-check-label" for="consent">
                                I confirm that the player has agreed to the <a href="../legal/privacy_policy.php" target="_blank">Privacy Policy</a> and consents to the processing of their personal data.
                            </label>
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

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Player Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="modalImage" class="img-fluid" alt="Full Size Image">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPlayerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Player</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editPlayerForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_player">
                        <input type="hidden" name="player_id" id="edit_player_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_id_number" class="form-label">ID Number *</label>
                                <input type="text" class="form-control" id="edit_id_number" name="id_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_gender" class="form-label">Gender *</label>
                                <select class="form-control" id="edit_gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_position" class="form-label">Position *</label>
                                <select class="form-control" id="edit_position" name="position" required>
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
                                <label for="edit_jersey_number" class="form-label">Jersey Number *</label>
                                <input type="number" class="form-control" id="edit_jersey_number" name="jersey_number" min="1" max="99" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_height_cm" class="form-label">Height (cm)</label>
                                <input type="number" class="form-control" id="edit_height_cm" name="height_cm" min="100" max="250">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_weight_kg" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="edit_weight_kg" name="weight_kg" min="30" max="150" step="0.1">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="edit_preferred_foot" class="form-label">Preferred Foot</label>
                            <select class="form-control" id="edit_preferred_foot" name="preferred_foot">
                                <option value="right">Right</option>
                                <option value="left">Left</option>
                                <option value="both">Both</option>
                            </select>
                        </div>

                        <hr>
                        <h5>Update Photos (Optional)</h5>
                        <p class="text-muted"><small>Only select new files if you want to replace the existing ones.</small></p>

                        <div class="mb-4">
                            <label for="edit_player_image" class="form-label">Player Photo</label>
                            <input type="file" class="form-control" id="edit_player_image" name="player_image" accept="image/*">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="edit_id_photo_front" class="form-label">ID Photo (Front)</label>
                                <input type="file" class="form-control" id="edit_id_photo_front" name="id_photo_front" accept="image/*">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="edit_id_photo_back" class="form-label">ID Photo (Back)</label>
                                <input type="file" class="form-control" id="edit_id_photo_back" name="id_photo_back" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">
                            <i class="fas fa-save me-2"></i>Update Player
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle image modal
            var imageModal = document.getElementById('imageModal');
            imageModal.addEventListener('show.bs.modal', function (event) {
                var triggerElement = event.relatedTarget;
                var imageSrc = triggerElement.getAttribute('data-img-src');
                var imageTitle = triggerElement.getAttribute('data-img-title');
                
                var modalImage = imageModal.querySelector('#modalImage');
                var modalTitle = imageModal.querySelector('#imageModalLabel');
                
                modalImage.src = imageSrc;
                modalTitle.textContent = imageTitle;
            });

            var editPlayerModal = document.getElementById('editPlayerModal');
            editPlayerModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var playerId = button.getAttribute('data-player-id');
                var firstName = button.getAttribute('data-first-name');
                var lastName = button.getAttribute('data-last-name');
                var email = button.getAttribute('data-email');
                var phone = button.getAttribute('data-phone');
                var idNumber = button.getAttribute('data-id-number');
                var gender = button.getAttribute('data-gender');
                var dob = button.getAttribute('data-dob');
                var position = button.getAttribute('data-position');
                var jerseyNumber = button.getAttribute('data-jersey-number');
                var heightCm = button.getAttribute('data-height-cm');
                var weightKg = button.getAttribute('data-weight-kg');
                var preferredFoot = button.getAttribute('data-preferred-foot');

                var modalBodyInputId = editPlayerModal.querySelector('#edit_player_id');
                var modalBodyInputFirstName = editPlayerModal.querySelector('#edit_first_name');
                var modalBodyInputLastName = editPlayerModal.querySelector('#edit_last_name');
                var modalBodyInputEmail = editPlayerModal.querySelector('#edit_email');
                var modalBodyInputPhone = editPlayerModal.querySelector('#edit_phone');
                var modalBodyInputIdNumber = editPlayerModal.querySelector('#edit_id_number');
                var modalBodyInputGender = editPlayerModal.querySelector('#edit_gender');
                var modalBodyInputDob = editPlayerModal.querySelector('#edit_date_of_birth');
                var modalBodyInputPosition = editPlayerModal.querySelector('#edit_position');
                var modalBodyInputJersey = editPlayerModal.querySelector('#edit_jersey_number');
                var modalBodyInputHeight = editPlayerModal.querySelector('#edit_height_cm');
                var modalBodyInputWeight = editPlayerModal.querySelector('#edit_weight_kg');
                var modalBodyInputFoot = editPlayerModal.querySelector('#edit_preferred_foot');

                modalBodyInputId.value = playerId;
                modalBodyInputFirstName.value = firstName;
                modalBodyInputLastName.value = lastName;
                modalBodyInputEmail.value = email;
                modalBodyInputPhone.value = phone;
                modalBodyInputIdNumber.value = idNumber;
                modalBodyInputGender.value = gender;
                modalBodyInputDob.value = dob;
                modalBodyInputPosition.value = position;
                modalBodyInputJersey.value = jerseyNumber;
                modalBodyInputHeight.value = heightCm;
                modalBodyInputWeight.value = weightKg;
                modalBodyInputFoot.value = preferredFoot;
            });
        });
    </script>
</body>
</html>