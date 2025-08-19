<?php
// Include essential files. The authentication functions are likely in 'helpers.php'.
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

// Check if user is logged in and has permission
if (!is_logged_in() || !has_permission('manage_players')) {
    redirect('../auth/login.php');
}

// CORRECTED: Use get_logged_in_user() instead of the deprecated get_current_user_data()
$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Get teams for the form - restrict captains to their own team only
if (has_role('captain')) {
    // CORRECTED: Filter teams by the coach_id column, which represents the user ID for a captain
    $teams = $db->fetchAll("
        SELECT t.*, w.name as ward_name
        FROM teams t
        JOIN wards w ON t.ward_id = w.id
        WHERE t.coach_id = ? AND t.status = 'active'
        ORDER BY t.name
    ", [$user['id']]);
} else {
    $teams = $db->fetchAll("
        SELECT t.*, w.name as ward_name
        FROM teams t
        JOIN wards w ON t.ward_id = w.id
        WHERE t.status = 'active'
        ORDER BY t.name
    ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $id_number = sanitize_input($_POST['id_number'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $position = $_POST['position'] ?? '';
    $jersey_number = (int)($_POST['jersey_number'] ?? 0);
    $height_cm = (int)($_POST['height_cm'] ?? 0);
    $weight_kg = (float)($_POST['weight_kg'] ?? 0);
    $preferred_foot = $_POST['preferred_foot'] ?? 'right';
    $team_id = (int)($_POST['team_id'] ?? 0);

    // --- Validation Block ---
    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required.';
    } elseif (!empty($email) && !validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($phone) && !validate_phone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (empty($id_number)) {
        $error = 'ID number is required.';
    } elseif (empty($date_of_birth)) {
        $error = 'Date of birth is required.';
    } elseif (empty($position)) {
        $error = 'Please select a position.';
    } elseif ($jersey_number < 1 || $jersey_number > 99) {
        $error = 'Jersey number must be between 1 and 99.';
    }

    if (empty($error)) {
        // Age validation
        $dob = new DateTime($date_of_birth);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        if ($age < 10 || $age > 45) {
            $error = 'Player must be between 10 and 45 years old.';
        }
    }

    if (empty($error)) {
        // Check if ID number is already registered
        $id_check = $db->fetchRow("SELECT id FROM users WHERE id_number = ?", [$id_number]);
        if ($id_check) {
            $error = 'A player with this ID number is already registered.';
        }
    }

    if (empty($error) && $team_id > 0) {
        // Check if team has reached maximum players (22)
        $player_count_result = $db->fetchRow("
            SELECT COUNT(*) as count FROM players
            WHERE team_id = ? AND is_active = 1
        ", [$team_id]);
        $current_players = $player_count_result['count'] ?? 0;

        if ($current_players >= 22) {
            $error = 'This team has already reached the maximum limit of 22 players.';
        }
    }

    if (empty($error) && $team_id > 0 && $jersey_number > 0) {
        // Check if jersey number is already taken in this team
        $jersey_check = $db->fetchRow("
            SELECT p.id
            FROM players p
            JOIN users u ON p.user_id = u.id
            WHERE p.team_id = ? AND p.jersey_number = ? AND u.is_active = 1
        ", [$team_id, $jersey_number]);
        if ($jersey_check) {
            $error = "Jersey number $jersey_number is already taken on this team.";
        }
    }

    if (empty($error) && has_role('captain')) {
        // If user is a captain, ensure they can only add players to their own team
        $captain_team_result = $db->fetchRow("
            SELECT id FROM teams
            WHERE coach_id = ?
        ", [$user['id']]);
        $captain_team = $captain_team_result['id'] ?? null;

        if ($team_id != $captain_team) {
            $error = 'Captains can only add players to their own team.';
        }
    }

    // --- Image Upload & Database Insertion Block ---
    if (empty($error)) {
        $db->beginTransaction();
        $player_image = null;
        $id_front_image = null;
        $id_back_image = null;

        try {
            // Handle player photo upload
            if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_result = upload_image($_FILES['player_image'], 'player', 'photo');
                if (!$upload_result['success']) {
                    throw new Exception('Player photo upload failed: ' . $upload_result['error']);
                }
                $player_image = $upload_result['path'];
            }

            // Handle ID front photo upload
            if (isset($_FILES['id_photo_front']) && $_FILES['id_photo_front']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_result = upload_image($_FILES['id_photo_front'], 'id', 'front');
                if (!$upload_result['success']) {
                    throw new Exception('ID Front photo upload failed: ' . $upload_result['error']);
                }
                $id_front_image = $upload_result['path'];
            }

            // Handle ID back photo upload
            if (isset($_FILES['id_photo_back']) && $_FILES['id_photo_back']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_result = upload_image($_FILES['id_photo_back'], 'id', 'back');
                if (!$upload_result['success']) {
                    throw new Exception('ID Back photo upload failed: ' . $upload_result['error']);
                }
                $id_back_image = $upload_result['path'];
            }

            // Create user account for player
            $username = strtolower($first_name . '.' . $last_name . '.' . time());
            $password_hash = password_hash('player123', PASSWORD_DEFAULT); // Default password

            $db->query("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number, is_active)
                VALUES (?, ?, ?, 'player', ?, ?, ?, ?, 1)
            ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number]);

            $user_id = $db->lastInsertId();

            // Create player profile
            $db->query("
                INSERT INTO players (user_id, team_id, position, jersey_number, height_cm, weight_kg, date_of_birth, preferred_foot, player_image, id_photo_front, id_photo_back, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ", [$user_id, $team_id, $position, $jersey_number, $height_cm, $weight_kg, $date_of_birth, $preferred_foot, $player_image, $id_front_image, $id_back_image]);

            $player_id = $db->lastInsertId();

            // Create player registration
            if ($team_id > 0) {
                $db->query("
                    INSERT INTO player_registrations (player_id, team_id, season_year, registration_date)
                    VALUES (?, ?, ?, CURDATE())
                ", [$player_id, $team_id, date('Y')]);
            }

            $db->commit();
            log_activity($user['id'], 'player_registration', "Registered player: $first_name $last_name");
            $success = "Player '$first_name $last_name' registered successfully! Username: $username, Password: player123";

            // Clear form data on success
            unset($_POST);

        } catch (Exception $e) {
            $db->rollBack();
            // Delete uploaded images if the database insert failed
            if ($player_image) {
                delete_image($player_image);
            }
            if ($id_front_image) {
                delete_image($id_front_image);
            }
            if ($id_back_image) {
                delete_image($id_back_image);
            }
            $error = 'Failed to register player. ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Player - <?php echo APP_NAME; ?></title>
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
                <div class="registration-card p-4">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-user me-2"></i>Register New Player</h2>
                        <p class="text-muted">Add a new player to the system</p>
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

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">
                                        <i class="fas fa-user me-2"></i>First Name *
                                    </label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">
                                        <i class="fas fa-user me-2"></i>Last Name *
                                    </label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>ID Number *
                                    </label>
                                    <input type="text" class="form-control" id="id_number" name="id_number"
                                        value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Date of Birth *
                                    </label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                        value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="position" class="form-label">
                                        <i class="fas fa-futbol me-2"></i>Position *
                                    </label>
                                    <select class="form-control" id="position" name="position" required>
                                        <option value="">Select Position</option>
                                        <option value="goalkeeper" <?php echo ($_POST['position'] ?? '') == 'goalkeeper' ? 'selected' : ''; ?>>Goalkeeper</option>
                                        <option value="defender" <?php echo ($_POST['position'] ?? '') == 'defender' ? 'selected' : ''; ?>>Defender</option>
                                        <option value="midfielder" <?php echo ($_POST['position'] ?? '') == 'midfielder' ? 'selected' : ''; ?>>Midfielder</option>
                                        <option value="forward" <?php echo ($_POST['position'] ?? '') == 'forward' ? 'selected' : ''; ?>>Forward</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="jersey_number" class="form-label">
                                        <i class="fas fa-hashtag me-2"></i>Jersey Number
                                    </label>
                                    <input type="number" class="form-control" id="jersey_number" name="jersey_number"
                                        value="<?php echo htmlspecialchars($_POST['jersey_number'] ?? ''); ?>"
                                        min="1" max="99">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="preferred_foot" class="form-label">
                                        <i class="fas fa-shoe-prints me-2"></i>Preferred Foot
                                    </label>
                                    <select class="form-control" id="preferred_foot" name="preferred_foot">
                                        <option value="right" <?php echo ($_POST['preferred_foot'] ?? 'right') == 'right' ? 'selected' : ''; ?>>Right</option>
                                        <option value="left" <?php echo ($_POST['preferred_foot'] ?? '') == 'left' ? 'selected' : ''; ?>>Left</option>
                                        <option value="both" <?php echo ($_POST['preferred_foot'] ?? '') == 'both' ? 'selected' : ''; ?>>Both</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="height_cm" class="form-label">
                                        <i class="fas fa-ruler-vertical me-2"></i>Height (cm)
                                    </label>
                                    <input type="number" class="form-control" id="height_cm" name="height_cm"
                                        value="<?php echo htmlspecialchars($_POST['height_cm'] ?? ''); ?>"
                                        min="100" max="250">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="weight_kg" class="form-label">
                                        <i class="fas fa-weight me-2"></i>Weight (kg)
                                    </label>
                                    <input type="number" class="form-control" id="weight_kg" name="weight_kg"
                                        value="<?php echo htmlspecialchars($_POST['weight_kg'] ?? ''); ?>"
                                        min="30" max="150" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="team_id" class="form-label">
                                        <i class="fas fa-users me-2"></i>Team (Optional)
                                    </label>
                                    <select class="form-control" id="team_id" name="team_id">
                                        <option value="">No Team</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id']; ?>"
                                                <?php echo ($_POST['team_id'] ?? '') == $team['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($team['name'] . ' (' . $team['ward_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Uploads</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="player_image" class="form-label">
                                        <i class="fas fa-camera me-2"></i>Player Photo
                                    </label>
                                    <input type="file" class="form-control" id="player_image" name="player_image"
                                        accept="image/*" onchange="previewImage(this, 'player-preview')">
                                    <small class="form-text text-muted">Upload player photo (JPG, PNG, GIF, max 5MB)</small>
                                    <div id="player-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="Player Preview" class="img-thumbnail" style="max-width: 150px; max-height: 200px;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_photo_front" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>ID Photo (Front)
                                    </label>
                                    <input type="file" class="form-control" id="id_photo_front" name="id_photo_front"
                                        accept="image/*" onchange="previewImage(this, 'id-front-preview')">
                                    <small class="form-text text-muted">Upload a clear photo of the ID card front.</small>
                                    <div id="id-front-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="ID Front Preview" class="img-thumbnail" style="max-width: 150px; max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_photo_back" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>ID Photo (Back)
                                    </label>
                                    <input type="file" class="form-control" id="id_photo_back" name="id_photo_back"
                                        accept="image/*" onchange="previewImage(this, 'id-back-preview')">
                                    <small class="form-text text-muted">Upload a clear photo of the ID card back.</small>
                                    <div id="id-back-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="ID Back Preview" class="img-thumbnail" style="max-width: 150px; max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-register">
                                <i class="fas fa-save me-2"></i>Register Player
                            </button>
                            <a href="../admin/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const img = preview.querySelector('img');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>