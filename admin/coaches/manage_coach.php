<?php
// admin/coaches/manage_coach.php - Manage coach information
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../auth/gmail_oauth.php';

// Check admin permissions
if (!GmailOAuth::isValidAdminSession()) {
    redirect('../../auth/admin_login.php');
}

$db = db();

$coach_id = (int)($_GET['id'] ?? 0);
if (!$coach_id) {
    redirect('../coaches.php');
}

// Get coach details
$coach = $db->fetchRow("
    SELECT 
        u.id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.phone,
        u.id_number,
        u.profile_image,
        u.is_active,
        u.username,
        c.license_number,
        c.license_type,
        c.experience_years,
        c.specialization,
        c.certifications,
        c.coach_image,
        c.team_id
    FROM users u
    LEFT JOIN coaches c ON u.id = c.user_id
    WHERE u.id = ? AND u.role = 'coach'
", [$coach_id]);

if (!$coach) {
    $_SESSION['error_message'] = 'Coach not found.';
    redirect('../coaches.php');
}

// Get available teams for assignment
$teams = $db->fetchAll("
    SELECT t.id, t.name, t.team_code,
           CASE WHEN c.team_id IS NOT NULL THEN 1 ELSE 0 END as has_coach
    FROM teams t
    LEFT JOIN coaches c ON t.id = c.team_id AND c.user_id != ?
    ORDER BY t.name ASC
", [$coach_id]);

// Get sub counties and wards for location selection
$sub_counties = $db->fetchAll("
    SELECT * FROM sub_counties 
    ORDER BY name
");

$wards = $db->fetchAll("
    SELECT w.*, sc.name as sub_county_name 
    FROM wards w 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    ORDER BY sc.name, w.name
");

// Get coach's current ward information
$coach_ward = $db->fetchRow("
    SELECT cr.ward_id, w.name as ward_name, w.sub_county_id, sc.name as sub_county_name
    FROM coach_registrations cr
    LEFT JOIN wards w ON cr.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE cr.user_id = ?
    ORDER BY cr.created_at DESC
    LIMIT 1
", [$coach_id]);

$error = $_SESSION['error_message'] ?? null;
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/image_upload.php';
    
    // Handle image upload
    $coach_image_path = $coach['coach_image'];
    if (isset($_FILES['coach_image']) && $_FILES['coach_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_image($_FILES['coach_image'], 'coaches', 'coach');
        if ($upload_result['success']) {
            $coach_image_path = $upload_result['path'];
        } else {
            $error = 'Image upload failed: ' . $upload_result['error'];
        }
    }
    
    if (empty($error)) {
        // Sanitize and validate input
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $id_number = sanitize_input($_POST['id_number']);
        $license_number = sanitize_input($_POST['license_number']);
        $license_type = sanitize_input($_POST['license_type']);
        $experience_years = (int)($_POST['experience_years'] ?? 0);
        $specialization = sanitize_input($_POST['specialization']);
        $certifications = sanitize_input($_POST['certifications']);
        $team_id = !empty($_POST['team_id']) ? (int)$_POST['team_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sub_county_id = (int)($_POST['sub_county_id'] ?? 0);
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error = 'First name, last name, and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email is already taken by another user
            $existing_user = $db->fetchRow('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $coach_id]);
            if ($existing_user) {
                $error = 'Email address is already in use by another user.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Update users table
                    $db->query(
                        'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, profile_image = ?, is_active = ? WHERE id = ?',
                        [$first_name, $last_name, $email, $phone, $id_number, $coach_image_path, $is_active, $coach_id]
                    );
                    
                    // Update coaches table
                    $db->query(
                        'UPDATE coaches SET license_number = ?, license_type = ?, experience_years = ?, specialization = ?, certifications = ?, coach_image = ?, team_id = ? WHERE user_id = ?',
                        [$license_number, $license_type, $experience_years, $specialization, $certifications, $coach_image_path, $team_id, $coach_id]
                    );
                    
                    // Update or insert coach registration with ward information
                    if ($ward_id > 0) {
                        $existing_registration = $db->fetchRow('SELECT id FROM coach_registrations WHERE user_id = ?', [$coach_id]);
                        if ($existing_registration) {
                            $db->query('UPDATE coach_registrations SET ward_id = ? WHERE user_id = ?', [$ward_id, $coach_id]);
                        } else {
                            $db->query('INSERT INTO coach_registrations (user_id, ward_id, status) VALUES (?, ?, "approved")', [$coach_id, $ward_id]);
                        }
                    }
                    
                    $db->commit();
                    $_SESSION['success_message'] = 'Coach information updated successfully.';
                    redirect('view_coach.php?id=' . $coach_id);
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Failed to update coach information: ' . $e->getMessage();
                }
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
    <title>Edit Coach - <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include '../sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="../coaches.php">Coaches</a></li>
                                <li class="breadcrumb-item"><a href="view_coach.php?id=<?php echo $coach['id']; ?>"><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </nav>
                        <h2><i class="fas fa-edit me-2"></i>Edit Coach</h2>
                    </div>
                    <div>
                        <a href="view_coach.php?id=<?php echo $coach['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to View
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-camera me-2"></i>Coach Photo</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php 
                                    $current_image = $coach['coach_image'] ?: $coach['profile_image'];
                                    if ($current_image): 
                                    ?>
                                        <img src="../../<?php echo htmlspecialchars($current_image); ?>" 
                                             alt="Coach Photo" 
                                             class="rounded-circle mb-3" 
                                             style="width: 150px; height: 150px; object-fit: cover;"
                                             id="imagePreview">
                                    <?php else: ?>
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto mb-3" 
                                             style="width: 150px; height: 150px; font-size: 48px;"
                                             id="imagePreview">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="coach_image" class="form-label">Upload New Photo</label>
                                        <input type="file" class="form-control" id="coach_image" name="coach_image" accept="image/*">
                                        <div class="form-text">Recommended: Square image, max 2MB</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($coach['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($coach['last_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($coach['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($coach['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="id_number" class="form-label">ID Number</label>
                                            <input type="text" class="form-control" id="id_number" name="id_number" 
                                                   value="<?php echo htmlspecialchars($coach['id_number'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                       <?php echo $coach['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Active Coach
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-certificate me-2"></i>Coaching Credentials</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="license_number" class="form-label">License Number</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" 
                                               value="<?php echo htmlspecialchars($coach['license_number'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="license_type" class="form-label">License Type</label>
                                        <select class="form-select" id="license_type" name="license_type">
                                            <option value="">Select License Type</option>
                                            <option value="caf_d" <?php echo $coach['license_type'] === 'caf_d' ? 'selected' : ''; ?>>CAF D License</option>
                                            <option value="caf_c" <?php echo $coach['license_type'] === 'caf_c' ? 'selected' : ''; ?>>CAF C License</option>
                                            <option value="caf_b" <?php echo $coach['license_type'] === 'caf_b' ? 'selected' : ''; ?>>CAF B License</option>
                                            <option value="caf_a" <?php echo $coach['license_type'] === 'caf_a' ? 'selected' : ''; ?>>CAF A License</option>
                                            <option value="fifa_pro" <?php echo $coach['license_type'] === 'fifa_pro' ? 'selected' : ''; ?>>FIFA Pro License</option>
                                            <option value="other" <?php echo $coach['license_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="experience_years" class="form-label">Years of Experience</label>
                                        <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                               min="0" max="50" value="<?php echo $coach['experience_years'] ?? 0; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="specialization" class="form-label">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" 
                                               value="<?php echo htmlspecialchars($coach['specialization'] ?? ''); ?>"
                                               placeholder="e.g., Youth Development, Goalkeeping, Fitness">
                                    </div>
                                    <div class="mb-3">
                                        <label for="certifications" class="form-label">Additional Certifications</label>
                                        <textarea class="form-control" id="certifications" name="certifications" rows="3"
                                                  placeholder="List any additional certifications or qualifications"><?php echo htmlspecialchars($coach['certifications'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-map-marker-alt me-2"></i>Location Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="sub_county_id" class="form-label">Sub County</label>
                                        <select class="form-select" id="sub_county_id" name="sub_county_id" onchange="filterWards()">
                                            <option value="">Select Sub County</option>
                                            <?php foreach ($sub_counties as $sub_county): ?>
                                                <option value="<?php echo $sub_county['id']; ?>" 
                                                        <?php echo ($coach_ward['sub_county_id'] ?? '') == $sub_county['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sub_county['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ward_id" class="form-label">Ward</label>
                                        <select class="form-select" id="ward_id" name="ward_id">
                                            <option value="">Select Ward</option>
                                            <?php foreach ($wards as $ward): ?>
                                                <option value="<?php echo $ward['id']; ?>" 
                                                        data-sub-county="<?php echo $ward['sub_county_id']; ?>"
                                                        <?php echo ($coach_ward['ward_id'] ?? '') == $ward['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ward['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-users me-2"></i>Team Assignment</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="team_id" class="form-label">Assign to Team</label>
                                        <select class="form-select" id="team_id" name="team_id">
                                            <option value="">No Team Assignment</option>
                                            <?php foreach ($teams as $team): ?>
                                                <option value="<?php echo $team['id']; ?>" 
                                                        <?php echo $coach['team_id'] == $team['id'] ? 'selected' : ''; ?>
                                                        <?php echo $team['has_coach'] && $coach['team_id'] != $team['id'] ? 'disabled' : ''; ?>>
                                                    <?php echo htmlspecialchars($team['name']); ?>
                                                    <?php if ($team['team_code']): ?>
                                                        (<?php echo htmlspecialchars($team['team_code']); ?>)
                                                    <?php endif; ?>
                                                    <?php if ($team['has_coach'] && $coach['team_id'] != $team['id']): ?>
                                                        - Already has coach
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">
                                            Each team can only have one coach. Teams with existing coaches are disabled.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <a href="view_coach.php?id=<?php echo $coach['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Coach
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Image preview functionality
document.getElementById('coach_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">';
        };
        reader.readAsDataURL(file);
    }
});

// Ward filtering functionality
function filterWards() {
    const subCountySelect = document.getElementById('sub_county_id');
    const wardSelect = document.getElementById('ward_id');
    const selectedSubCounty = subCountySelect.value;
    
    // Clear ward selection
    wardSelect.innerHTML = '<option value="">Select Ward</option>';
    
    if (selectedSubCounty) {
        // Get all ward options and filter by sub county
        <?php foreach ($wards as $ward): ?>
        if ('<?php echo $ward['sub_county_id']; ?>' === selectedSubCounty) {
            const option = document.createElement('option');
            option.value = '<?php echo $ward['id']; ?>';
            option.textContent = '<?php echo htmlspecialchars($ward['name']); ?>';
            option.setAttribute('data-sub-county', '<?php echo $ward['sub_county_id']; ?>');
            wardSelect.appendChild(option);
        }
        <?php endforeach; ?>
    }
}

// Initialize ward filtering on page load
document.addEventListener('DOMContentLoaded', function() {
    filterWards();
});
</script>
</body>
</html>
