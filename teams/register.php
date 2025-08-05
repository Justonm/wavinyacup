<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

// Check if user is logged in and has permission
if (!is_logged_in() || !has_permission('manage_teams')) {
    redirect('../auth/login.php');
}

$user = get_current_user_data();
$db = db();
$error = '';
$success = '';

// Get wards for the form
$wards = $db->fetchAll("
    SELECT w.*, sc.name as sub_county_name 
    FROM wards w 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    ORDER BY sc.name, w.name
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = sanitize_input($_POST['team_name'] ?? '');
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    $founded_year = (int)($_POST['founded_year'] ?? date('Y'));
    $home_ground = sanitize_input($_POST['home_ground'] ?? '');
    $team_colors = sanitize_input($_POST['team_colors'] ?? '');
    
    // Validation
    if (empty($team_name)) {
        $error = 'Team name is required.';
    } elseif ($ward_id <= 0) {
        $error = 'Please select a ward.';
    } elseif ($founded_year < 1900 || $founded_year > date('Y')) {
        $error = 'Invalid founded year.';
    } else {
        // Handle image uploads
        $logo_path = null;
        $team_photo = null;
        
        // Handle team logo upload
        if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['team_logo'], 'team', 'logo');
            if (!$upload_result['success']) {
                $error = 'Logo upload failed: ' . $upload_result['error'];
            } else {
                $logo_path = $upload_result['path'];
            }
        }
        
        // Handle team photo upload
        if (empty($error) && isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['team_photo'], 'team', 'photo');
            if (!$upload_result['success']) {
                $error = 'Team photo upload failed: ' . $upload_result['error'];
            } else {
                $team_photo = $upload_result['path'];
            }
        }
        
        if (empty($error)) {
            // Generate unique team code
            $ward = $db->fetchRow("SELECT code FROM wards WHERE id = ?", [$ward_id]);
            $team_code = generate_team_code($ward['code']);
            
            try {
                $db->query("
                    INSERT INTO teams (name, ward_id, coach_id, team_code, founded_year, home_ground, team_colors, logo_path, team_photo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [$team_name, $ward_id, $user['id'], $team_code, $founded_year, $home_ground, $team_colors, $logo_path, $team_photo]);
                
                $team_id = $db->lastInsertId();
                
                // Create team registration
                $db->query("
                    INSERT INTO team_registrations (team_id, season_year, registration_date) 
                    VALUES (?, ?, CURDATE())
                ", [$team_id, date('Y')]);
                
                log_activity($user['id'], 'team_registration', "Registered team: $team_name");
                $success = "Team '$team_name' registered successfully! Team Code: $team_code";
                
            } catch (Exception $e) {
                // Delete uploaded images if database insert failed
                if ($logo_path) {
                    delete_image($logo_path);
                }
                if ($team_photo) {
                    delete_image($team_photo);
                }
                $error = 'Failed to register team. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Team - <?php echo APP_NAME; ?></title>
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
            <div class="col-md-8">
                <div class="registration-card p-4">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-futbol me-2"></i>Register New Team</h2>
                        <p class="text-muted">Create a new team for Machakos County</p>
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
                                    <label for="team_name" class="form-label">
                                        <i class="fas fa-users me-2"></i>Team Name *
                                    </label>
                                    <input type="text" class="form-control" id="team_name" name="team_name" 
                                           value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ward_id" class="form-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Ward *
                                    </label>
                                    <select class="form-control" id="ward_id" name="ward_id" required>
                                        <option value="">Select Ward</option>
                                        <?php foreach ($wards as $ward): ?>
                                            <option value="<?php echo $ward['id']; ?>" 
                                                    <?php echo ($_POST['ward_id'] ?? '') == $ward['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ward['name'] . ' (' . $ward['sub_county_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="founded_year" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Founded Year
                                    </label>
                                    <input type="number" class="form-control" id="founded_year" name="founded_year" 
                                           value="<?php echo htmlspecialchars($_POST['founded_year'] ?? date('Y')); ?>" 
                                           min="1900" max="<?php echo date('Y'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="home_ground" class="form-label">
                                        <i class="fas fa-map me-2"></i>Home Ground
                                    </label>
                                    <input type="text" class="form-control" id="home_ground" name="home_ground" 
                                           value="<?php echo htmlspecialchars($_POST['home_ground'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="team_colors" class="form-label">
                                        <i class="fas fa-palette me-2"></i>Team Colors
                                    </label>
                                    <input type="text" class="form-control" id="team_colors" name="team_colors" 
                                           value="<?php echo htmlspecialchars($_POST['team_colors'] ?? ''); ?>" 
                                           placeholder="e.g., Blue and White">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="team_logo" class="form-label">
                                        <i class="fas fa-image me-2"></i>Team Logo
                                    </label>
                                    <input type="file" class="form-control" id="team_logo" name="team_logo" 
                                           accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                    <small class="form-text text-muted">Upload team logo (JPG, PNG, GIF, max 5MB)</small>
                                    <div id="logo-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="Logo Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label for="team_photo" class="form-label">
                                        <i class="fas fa-camera me-2"></i>Team Photo
                                    </label>
                                    <input type="file" class="form-control" id="team_photo" name="team_photo" 
                                           accept="image/*" onchange="previewImage(this, 'photo-preview')">
                                    <small class="form-text text-muted">Upload team photo showing team members (JPG, PNG, GIF, max 5MB)</small>
                                    <div id="photo-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="Team Photo Preview" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-register">
                                <i class="fas fa-save me-2"></i>Register Team
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