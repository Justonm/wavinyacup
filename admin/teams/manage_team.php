<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Check if user is logged in and has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../../auth/login.php');
}

$db = db();
$user = get_logged_in_user();

$team_id = $_GET['id'] ?? null;
$is_editing = !is_null($team_id);

$team = [
    'name' => '',
    'team_description' => '',
    'ward_id' => '',
    'owner_name' => '',
    'owner_id_number' => '',
    'owner_phone' => '',
    'founded_year' => date('Y'),
    'home_ground' => '',
    'team_colors' => '',
    'logo_path' => '',
    'team_photo' => '',
    'status' => 'active',
];
$page_title = 'Add New Team';
$button_text = 'Create Team';

if ($is_editing) {
    $team = $db->fetchRow('SELECT * FROM teams WHERE id = ?', [$team_id]);
    if (!$team) {
        $_SESSION['error_message'] = 'Team not found.';
        redirect('../teams.php');
    }
    $page_title = 'Edit Team';
    $button_text = 'Update Team';
}

// Fetch wards for the dropdown
$wards = $db->fetchAll('SELECT id, name FROM wards ORDER BY name ASC');

$error = $_SESSION['error_message'] ?? null;
$success = $_SESSION['success_message'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/image_upload.php';
    
    // Handle image uploads
    $logo_path = $team['logo_path'] ?? null;
    $team_photo_path = $team['team_photo'] ?? null;
    
    // Handle team logo upload
    if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_image($_FILES['team_logo'], 'teams', 'logo');
        if ($upload_result['success']) {
            $logo_path = $upload_result['path'];
        } else {
            $error = 'Logo upload failed: ' . $upload_result['error'];
        }
    }
    
    // Handle team photo upload
    if (empty($error) && isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_image($_FILES['team_photo'], 'teams', 'photo');
        if ($upload_result['success']) {
            $team_photo_path = $upload_result['path'];
        } else {
            $error = 'Team photo upload failed: ' . $upload_result['error'];
        }
    }
    
    $team_name = sanitize_input($_POST['team_name']);
    $team_description = sanitize_input($_POST['team_description']);
    $ward_id = (int)$_POST['ward_id'];
    $owner_name = sanitize_input($_POST['owner_name']);
    $owner_id_number = sanitize_input($_POST['owner_id_number']);
    $owner_phone = sanitize_input($_POST['owner_phone']);
    $founded_year = (int)($_POST['founded_year'] ?? date('Y'));
    $home_ground = sanitize_input($_POST['home_ground']);
    $team_colors = sanitize_input($_POST['team_colors']);
    $status = sanitize_input($_POST['status']);

        if (empty($team_name) || empty($ward_id)) {
        $error = 'Team name and ward are required.';
    } elseif (empty($owner_name) || empty($owner_id_number) || empty($owner_phone)) {
        $error = 'Owner information (name, ID number, and phone) is required.';
    } elseif ($founded_year < 1900 || $founded_year > date('Y')) {
        $error = 'Invalid founded year.';
    } else {
        try {
            if ($is_editing) {
                // Update existing team
                $db->query(
                    'UPDATE teams SET name = ?, team_description = ?, ward_id = ?, owner_name = ?, owner_id_number = ?, owner_phone = ?, founded_year = ?, home_ground = ?, team_colors = ?, logo_path = ?, team_photo = ?, status = ? WHERE id = ?',
                    [$team_name, $team_description, $ward_id, $owner_name, $owner_id_number, $owner_phone, $founded_year, $home_ground, $team_colors, $logo_path, $team_photo_path, $status, $team_id]
                );
                $_SESSION['success_message'] = 'Team updated successfully.';
            } else {
                // Create new team
                $team_code = generate_team_code($ward_id);
                $db->query(
                    'INSERT INTO teams (name, team_description, team_code, ward_id, owner_name, owner_id_number, owner_phone, founded_year, home_ground, team_colors, logo_path, team_photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$team_name, $team_description, $team_code, $ward_id, $owner_name, $owner_id_number, $owner_phone, $founded_year, $home_ground, $team_colors, $logo_path, $team_photo_path, $status]
                );
                $_SESSION['success_message'] = 'Team created successfully.';
            }
            redirect('../teams.php');
        } catch (Exception $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}

unset($_SESSION['error_message'], $_SESSION['success_message']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-users me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="team_name" class="form-label">
                                            <i class="fas fa-users me-2"></i>Team Name *
                                        </label>
                                        <input type="text" class="form-control" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ward_id" class="form-label">
                                            <i class="fas fa-map-marker-alt me-2"></i>Ward *
                                        </label>
                                        <select class="form-select" id="ward_id" name="ward_id" required>
                                            <option value="">Select Ward</option>
                                            <?php foreach ($wards as $ward): ?>
                                                <option value="<?php echo $ward['id']; ?>" <?php echo ($team['ward_id'] == $ward['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ward['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="team_description" class="form-label">
                                    <i class="fas fa-info-circle me-2"></i>Team Description
                                </label>
                                <textarea class="form-control" id="team_description" name="team_description" rows="3" placeholder="Brief description of the team"><?php echo htmlspecialchars($team['team_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="owner_name" class="form-label">
                                            <i class="fas fa-user-tie me-2"></i>Team Owner Name *
                                        </label>
                                        <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($team['owner_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="owner_id_number" class="form-label">
                                            <i class="fas fa-id-card me-2"></i>Owner ID Number *
                                        </label>
                                        <input type="text" class="form-control" id="owner_id_number" name="owner_id_number" value="<?php echo htmlspecialchars($team['owner_id_number'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="owner_phone" class="form-label">
                                            <i class="fas fa-phone me-2"></i>Owner Phone Number *
                                        </label>
                                        <input type="tel" class="form-control" id="owner_phone" name="owner_phone" value="<?php echo htmlspecialchars($team['owner_phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="founded_year" class="form-label">
                                            <i class="fas fa-calendar me-2"></i>Founded Year
                                        </label>
                                        <input type="number" class="form-control" id="founded_year" name="founded_year" value="<?php echo htmlspecialchars($team['founded_year'] ?? date('Y')); ?>" min="1900" max="<?php echo date('Y'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="home_ground" class="form-label">
                                            <i class="fas fa-map me-2"></i>Home Ground
                                        </label>
                                        <input type="text" class="form-control" id="home_ground" name="home_ground" value="<?php echo htmlspecialchars($team['home_ground'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="team_colors" class="form-label">
                                            <i class="fas fa-palette me-2"></i>Team Colors
                                        </label>
                                        <input type="text" class="form-control" id="team_colors" name="team_colors" value="<?php echo htmlspecialchars($team['team_colors'] ?? ''); ?>" placeholder="e.g., Blue and White">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="team_logo" class="form-label">
                                            <i class="fas fa-image me-2"></i>Team Logo
                                        </label>
                                        <input type="file" class="form-control" id="team_logo" name="team_logo" accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                        <small class="form-text text-muted">Upload team logo (JPG, PNG, GIF, max 5MB)</small>
                                        <?php if (!empty($team['logo_path'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo APP_URL . '/uploads/' . htmlspecialchars($team['logo_path']); ?>" alt="Team Logo" style="max-width: 150px; height: auto;">
                                            </div>
                                        <?php endif; ?>
                                        <div id="logo-preview" class="mt-2" style="display: none;">
                                            <img src="" alt="Logo Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="team_photo" class="form-label">
                                            <i class="fas fa-camera me-2"></i>Team Photo
                                        </label>
                                        <input type="file" class="form-control" id="team_photo" name="team_photo" accept="image/*" onchange="previewImage(this, 'photo-preview')">
                                        <small class="form-text text-muted">Upload team photo showing team members (JPG, PNG, GIF, max 5MB)</small>
                                        <?php if (!empty($team['team_photo'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo APP_URL . '/uploads/' . htmlspecialchars($team['team_photo']); ?>" alt="Team Photo" style="max-width: 150px; height: auto;">
                                            </div>
                                        <?php endif; ?>
                                        <div id="photo-preview" class="mt-2" style="display: none;">
                                            <img src="" alt="Team Photo Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-toggle-on me-2"></i>Status
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo ($team['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($team['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo ($team['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="../teams.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?php echo $button_text; ?></button>
                            </div>
                        </form>
                    </div>
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
