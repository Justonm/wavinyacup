<?php
// coaches/register.php

// Include necessary files. The helpers.php file contains functions like
// is_logged_in(), has_permission(), and the new get_logged_in_user().

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

// Check if user is logged in and has permission
if (!is_logged_in() || !has_permission('manage_coaches')) {
    redirect('../auth/login.php');
}

// Generate and store a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Get sub counties and wards for the form
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid form submission. Please try again.';
        // Regenerate token to prevent resubmission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $id_number = sanitize_input($_POST['id_number'] ?? '');
        $license_number = sanitize_input($_POST['license_number'] ?? '');
        $license_type = $_POST['license_type'] ?? '';
        $experience_years = (int)($_POST['experience_years'] ?? 0);
        $sub_county_id = (int)($_POST['sub_county_id'] ?? 0);
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $specialization = sanitize_input($_POST['specialization'] ?? '');
        $certifications = sanitize_input($_POST['certifications'] ?? '');
        $team_name = sanitize_input($_POST['team_name'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name)) {
            $error = 'First name and last name are required.';
        } elseif (empty($email) || !validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($phone) || !validate_phone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (empty($id_number)) {
            $error = 'ID number is required.';
        } elseif (empty($license_number)) {
            $error = 'License number is required.';
        } elseif (empty($license_type)) {
            $error = 'Please select a license type.';
        } elseif (empty($team_name)) {
            $error = 'Team name is required.';
        } elseif ($sub_county_id <= 0) {
            $error = 'Please select a sub county.';
        } elseif ($ward_id <= 0) {
            $error = 'Please select a ward.';
        } elseif ($experience_years < 0 || $experience_years > 50) {
            $error = 'Experience years must be between 0 and 50.';
        } else {
            // Check if email or ID number already exists
            $existing_user = $db->fetchRow("SELECT id FROM users WHERE email = ? OR id_number = ?", [$email, $id_number]);
            if ($existing_user) {
                $error = 'A user with this email or ID number already exists.';
            } else {
                // Handle image upload
                $coach_image = null;
                if (isset($_FILES['coach_image']) && $_FILES['coach_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = upload_image($_FILES['coach_image'], 'coaches', 'photo');
                    if (!$upload_result['success']) {
                        $error = 'Image upload failed: ' . $upload_result['error'];
                    } else {
                        $coach_image = $upload_result['path'];
                    }
                }
                
                if (empty($error)) {
                    try {
                        $db->beginTransaction();

                        // Auto-generate a secure password
                        $password = bin2hex(random_bytes(8)); // e.g., 'coach1234'
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $username = strtolower(str_replace(' ', '', $first_name) . '.' . str_replace(' ', '', $last_name));

                        // Check if username exists and append a number if it does
                        $existing_user_by_username = $db->fetchRow("SELECT id FROM users WHERE username = ?", [$username]);
                        if ($existing_user_by_username) {
                            $username .= rand(100, 999);
                        }

                        // 1. Create user account with approved status
                        $db->query("
                            INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number, approval_status) 
                            VALUES (?, ?, ?, 'coach', ?, ?, ?, ?, 'approved')
                        ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number]);
                        
                        $user_id = $db->lastInsertId();

                        // 2. Create the team
                        $db->query("INSERT INTO teams (name, ward_id, status) VALUES (?, ?, 'active')", [$team_name, $ward_id]);
                        $team_id = $db->lastInsertId();
                        
                        // 3. Create coach profile and link to user and team
                        $db->query("
                            INSERT INTO coaches (user_id, team_id, license_number, license_type, experience_years, specialization, certifications, image_path, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                        ", [$user_id, $team_id, $license_number, $license_type, $experience_years, $specialization, $certifications, $coach_image]);
                        
                        // 4. Update team with coach_id
                        $db->query("UPDATE teams SET coach_id = ? WHERE id = ?", [$db->lastInsertId(), $team_id]);

                        // 5. Create coach registration record for history
                        $db->query("
                            INSERT INTO coach_registrations (user_id, team_name, ward_id, status) 
                            VALUES (?, ?, ?, 'approved')
                        ", [$user_id, $team_name, $ward_id]);
                        
                        $db->commit();
                        
                        // Send welcome email to coach with credentials
                        $email_subject = "Welcome to " . APP_NAME . " - Your Coach Account is Ready!";
                        $email_body = get_email_template('emails/coach_approval.php', [
                            'coach_name' => $first_name,
                            'team_name' => $team_name,
                            'username' => $username,
                            'password' => $password
                        ]);

                        queue_email($email, $email_subject, $email_body);

                        log_activity($user['id'], 'coach_registration', "Admin registered coach: $first_name $last_name for team $team_name");
                        $success = "Coach '$first_name $last_name' registered and approved successfully! A welcome email with login credentials has been sent.";
                        
                        // Unset POST data to clear form on success
                        $_POST = array();
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        
                        // Delete uploaded image if database insert failed
                        if ($coach_image) {
                            delete_image($coach_image);
                        }
                        $error = 'Registration failed. Please try again. Error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Register Coach'; include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body class="registration-page">
    <div class="container">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
            <h4 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h4>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="registration-card">
                    <div class="registration-card-header text-center">
                        <h2 class="mb-1"><i class="fas fa-user-plus me-2"></i>Register New Coach</h2>
                        <p class="mb-0 text-light op-7">Add a new coach to the system</p>
                    </div>
                    <div class="registration-card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                            <div class="text-center">
                                <a href="register.php" class="btn btn-secondary">Register Another Coach</a>
                                <a href="../admin/coaches.php" class="btn btn-primary">View All Coaches</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-user me-2"></i>Personal Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required 
                                                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name *</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required
                                                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Address *</label>
                                                <input type="email" class="form-control" id="email" name="email" required
                                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone Number *</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" required
                                                        placeholder="e.g., +254712345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="id_number" class="form-label">ID Number *</label>
                                                <input type="text" class="form-control" id="id_number" name="id_number" required
                                                        value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="coach_image" class="form-label">
                                                    <i class="fas fa-camera me-2"></i>Profile Photo
                                                </label>
                                                <input type="file" class="form-control" id="coach_image" name="coach_image" 
                                                        accept="image/*" onchange="previewImage(this, 'coach-preview')">
                                                <small class="form-text text-muted">Upload your photo (JPG, PNG, GIF, max 5MB)</small>
                                                <div id="coach-preview" class="mt-2" style="display: none;">
                                                    <img src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-certificate me-2"></i>Coaching Credentials</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="license_number" class="form-label">License Number *</label>
                                                <input type="text" class="form-control" id="license_number" name="license_number" required
                                                        value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="license_type" class="form-label">License Type *</label>
                                                <select class="form-control" id="license_type" name="license_type" required>
                                                    <option value="">Select License Type</option>
                                                    <option value="basic" <?php echo ($_POST['license_type'] ?? '') === 'basic' ? 'selected' : ''; ?>>Basic</option>
                                                    <option value="intermediate" <?php echo ($_POST['license_type'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                    <option value="advanced" <?php echo ($_POST['license_type'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                                    <option value="professional" <?php echo ($_POST['license_type'] ?? '') === 'professional' ? 'selected' : ''; ?>>Professional</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="experience_years" class="form-label">Years of Experience</label>
                                                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                                        min="0" max="50" value="<?php echo htmlspecialchars($_POST['experience_years'] ?? '0'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="specialization" class="form-label">Specialization</label>
                                                <input type="text" class="form-control" id="specialization" name="specialization" 
                                                        placeholder="e.g., Youth Development, Tactics" value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="certifications" class="form-label">Additional Certifications</label>
                                        <textarea class="form-control" id="certifications" name="certifications" rows="3" 
                                                placeholder="List any additional certifications or qualifications"><?php echo htmlspecialchars($_POST['certifications'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-users me-2"></i>Team Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="team_name" class="form-label">Team Name *</label>
                                                <input type="text" class="form-control" id="team_name" name="team_name" required 
                                                        placeholder="Enter the new team's name" value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sub_county_id" class="form-label">Sub County *</label>
                                                <select class="form-control" id="sub_county_id" name="sub_county_id" required onchange="filterWards()">
                                                    <option value="">Select Sub County</option>
                                                    <?php foreach ($sub_counties as $sub_county): ?>
                                                        <option value="<?php echo $sub_county['id']; ?>" <?php echo ($_POST['sub_county_id'] ?? '') == $sub_county['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($sub_county['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="ward_id" class="form-label">Ward *</label>
                                                <select class="form-control" id="ward_id" name="ward_id" required>
                                                    <option value="">Select Ward</option>
                                                    <?php foreach ($wards as $ward): ?>
                                                        <option value="<?php echo $ward['id']; ?>" 
                                                                data-sub-county="<?php echo $ward['sub_county_id']; ?>"
                                                                <?php echo ($_POST['ward_id'] ?? '') == $ward['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($ward['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-register text-white">
                                        <i class="fas fa-paper-plane me-2"></i>Register Coach
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">Already have a coach? <a href="../admin/coaches.php">View Coaches</a></p>
                        </div>
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

        function filterWards() {
            const subCountySelect = document.getElementById('sub_county_id');
            const wardSelect = document.getElementById('ward_id');
            const selectedSubCounty = subCountySelect.value;
            
            // Clear ward selection
            wardSelect.innerHTML = '<option value="">Select Ward</option>';
            
            if (selectedSubCounty) {
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
    </script>
</body>
</html>