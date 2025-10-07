<?php
// coaches/self_register.php - Coach Self Registration Page
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';


$db = db();
$error = '';
$success = '';

// Generate and store a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


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
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
        // Regenerate token to prevent resubmission and ensure the form gets the new token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token'];
    } else {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $id_number = sanitize_input($_POST['id_number'] ?? '');
        $license_number = sanitize_input($_POST['license_number'] ?? '');
        $license_type = $_POST['license_type'] ?? '';
        $experience_years = (int)($_POST['experience_years'] ?? 0);
        $team_name = sanitize_input($_POST['team_name'] ?? '');
        $team_description = sanitize_input($_POST['team_description'] ?? '');
        $founded_year = (int)($_POST['founded_year'] ?? date('Y'));
        $home_ground = sanitize_input($_POST['home_ground'] ?? '');
        $team_colors = sanitize_input($_POST['team_colors'] ?? '');
        $sub_county_id = (int)($_POST['sub_county_id'] ?? 0);
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $specialization = sanitize_input($_POST['specialization'] ?? '');
        $certifications = sanitize_input($_POST['certifications'] ?? '');
        $consent = isset($_POST['consent']);
        
        // Validation
        if (empty($first_name) || empty($last_name)) {
            $error = 'First name and last name are required.';
        } elseif (empty($email) || !validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($phone) || !validate_phone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (empty($id_number)) {
            $error = 'ID number is required.';
        // --- START NEW MANDATORY CHECK ---
        } elseif (!isset($_FILES['coach_image']) || $_FILES['coach_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'A profile photo is required for registration.';
        // --- END NEW MANDATORY CHECK ---
        } elseif (empty($license_number)) {
            $error = 'License number is required.';
        } elseif (empty($license_type)) {
            $error = 'Please select a license type.';
        } elseif (empty($team_name)) {
            $error = 'Team name is required.';
        } elseif ($founded_year < 1900 || $founded_year > date('Y')) {
            $error = 'Invalid founded year.';
        } elseif ($sub_county_id <= 0) {
            $error = 'Please select a sub county.';
        } elseif ($ward_id <= 0) {
            $error = 'Please select a ward.';
        } elseif ($experience_years < 0 || $experience_years > 50) {
            $error = 'Experience years must be between 0 and 50.';
        } elseif (!$consent) {
            $error = 'You must agree to the privacy policy to register.';
        } else {
            // Enforce mandatory uploads for photos during self registration
            if (!isset($_FILES['coach_image']) || $_FILES['coach_image']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'Profile photo is required.';
            } elseif (!isset($_FILES['team_logo']) || $_FILES['team_logo']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'Team logo is required.';
            } elseif (!isset($_FILES['team_photo']) || $_FILES['team_photo']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'Team photo is required.';
            }

            if (empty($error)) {
                // Check if email or ID number already exists
                $existing_user = $db->fetchRow("SELECT id FROM users WHERE email = ? OR id_number = ?", [$email, $id_number]);
                if ($existing_user) {
                    $error = 'A user with this email or ID number already exists.';
                } else {
                // Handle image uploads
                $coach_image = null;
                $team_logo = null;
                $team_photo = null;
                
                // Handle coach image upload (Now mandatory and checked above)
                if (isset($_FILES['coach_image']) && $_FILES['coach_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = upload_image($_FILES['coach_image'], 'coaches', 'photo');
                    if (!$upload_result['success']) {
                        $error = 'Coach image upload failed: ' . $upload_result['error'];
                    } else {
                        $coach_image = $upload_result['path'];
                    }
                }
                
                // Handle team logo upload
                if (empty($error) && isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = upload_image($_FILES['team_logo'], 'teams', 'logo');
                    if (!$upload_result['success']) {
                        $error = 'Team logo upload failed: ' . $upload_result['error'];
                    } else {
                        $team_logo = $upload_result['path'];
                    }
                }
                
                // Handle team photo upload
                if (empty($error) && isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = upload_image($_FILES['team_photo'], 'teams', 'photo');
                    if (!$upload_result['success']) {
                        $error = 'Team photo upload failed: ' . $upload_result['error'];
                    } else {
                        $team_photo = $upload_result['path'];
                    }
                }
                
                if (empty($error)) {
                    try {
                        $db->beginTransaction();

                        // Set a secure, unusable password placeholder.
                        $password_hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                        
                        // Create a temporary username. This will be updated on approval.
                        $username = 'pending_' . bin2hex(random_bytes(8));

                        $consent_timestamp = date('Y-m-d H:i:s');
                        // 1. Create user account with pending status
                        $db->query("
                            INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number, approval_status, consent_given_at) 
                            VALUES (?, ?, ?, 'coach', ?, ?, ?, ?, 'pending', ?)
                        ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number, $consent_timestamp]);
                        
                        $user_id = $db->lastInsertId();
                        
                        // 2. Create coach profile, now including coach_image
                        $db->query("
                            INSERT INTO coaches (user_id, license_number, license_type, experience_years, specialization, certifications, coach_image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ", [$user_id, $license_number, $license_type, $experience_years, $specialization, $certifications, $coach_image]);
                        
                        // 3. Create coach registration record with pending status
                        $db->query("
                            INSERT INTO coach_registrations (user_id, team_name, team_description, founded_year, home_ground, team_colors, team_logo, team_photo, ward_id, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                        ", [$user_id, $team_name, $team_description, $founded_year, $home_ground, $team_colors, $team_logo, $team_photo, $ward_id]);
                        
                        $db->commit();
                        
                        // Fetch the ward name for the email
                        $ward_row = $db->fetchRow("SELECT name FROM wards WHERE id = ?", [$ward_id]);
                        $ward_name = $ward_row['name'] ?? 'N/A';

                        // Send notification email to admin
                        $admin_email_subject = "New Coach Registration - " . APP_NAME;
                        $admin_email_body = "
                            <h2>New Coach Registration Pending Approval</h2>
                            <p>A new coach has registered and requires your approval:</p>
                            
                            <h3>Coach Information</h3>
                            <ul>
                                <li><strong>Name:</strong> {$first_name} {$last_name}</li>
                                <li><strong>Email:</strong> {$email}</li>
                                <li><strong>Phone:</strong> {$phone}</li>
                                <li><strong>ID Number:</strong> {$id_number}</li>
                            </ul>
                            
                            <h3>Team Information</h3>
                            <ul>
                                <li><strong>Team Name:</strong> {$team_name}</li>
                                <li><strong>Team Description:</strong> {$team_description}</li>
                                <li><strong>Founded Year:</strong> {$founded_year}</li>
                                <li><strong>Home Ground:</strong> {$home_ground}</li>
                                <li><strong>Team Colors:</strong> {$team_colors}</li>
                                <li><strong>Ward:</strong> {$ward_name}</li>
                            </ul>
                            
                            <h3>Coaching Credentials</h3>
                            <ul>
                                <li><strong>License Number:</strong> {$license_number}</li>
                                <li><strong>License Type:</strong> {$license_type}</li>
                                <li><strong>Experience:</strong> {$experience_years} years</li>
                                <li><strong>Specialization:</strong> {$specialization}</li>
                            </ul>
                            
                            <p><a href='" . APP_URL . "/admin/pending_coaches.php' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Review Application</a></p>
                            
                            <p>Please review and approve/reject this registration at your earliest convenience.</p>
                            
                            <p>Best regards,<br>" . APP_NAME . " System</p>
                        ";
                        
                        queue_email(APP_EMAIL, $admin_email_subject, $admin_email_body);
                        
                        $success = "Registration submitted successfully! Your application is pending admin approval. You will receive an email with your login credentials once approved.";
                        
                        // Unset POST data to clear form on success
                        $_POST = array();
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        
                        // Delete uploaded images if database insert failed
                        if ($coach_image) {
                            delete_image($coach_image);
                        }
                        if ($team_logo) {
                            delete_image($team_logo);
                        }
                        if ($team_photo) {
                            delete_image($team_photo);
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
    <?php $page_title = 'Coach Registration'; include dirname(__DIR__) . '/includes/head.php'; ?>
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
                        <h2 class="mb-1"><i class="fas fa-user-plus me-2"></i>Coach Registration</h2>
                        <p class="mb-0 text-light op-7">Register as a coach and create your team</p>
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
                                <a href="../auth/login.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data">
                                
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
                                                    <i class="fas fa-camera me-2"></i>Profile Photo *
                                                </label>
                                                <input type="file" class="form-control" id="coach_image" name="coach_image" required
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
                                                <label for="team_name" class="form-label">
                                                    <i class="fas fa-users me-2"></i>Team Name *
                                                </label>
                                                <input type="text" class="form-control" id="team_name" name="team_name" required 
                                                        placeholder="Enter your team name" value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sub_county_id" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-2"></i>Sub County *
                                                </label>
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
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label for="team_description" class="form-label">
                                                    <i class="fas fa-info-circle me-2"></i>Team Description
                                                </label>
                                                <textarea class="form-control" id="team_description" name="team_description" rows="3"
                                                    placeholder="Brief description of the team"><?php echo htmlspecialchars($_POST['team_description'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="ward_id" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-2"></i>Ward *
                                                </label>
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
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="home_ground" class="form-label">
                                                    <i class="fas fa-map me-2"></i>Home Ground
                                                </label>
                                                <input type="text" class="form-control" id="home_ground" name="home_ground" 
                                                        value="<?php echo htmlspecialchars($_POST['home_ground'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="team_colors" class="form-label">
                                                    <i class="fas fa-palette me-2"></i>Team Colors
                                                </label>
                                                <input type="text" class="form-control" id="team_colors" name="team_colors" 
                                                        value="<?php echo htmlspecialchars($_POST['team_colors'] ?? ''); ?>" 
                                                        placeholder="e.g., Blue and White">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="team_logo" class="form-label">
                                                    <i class="fas fa-image me-2"></i>Team Logo *
                                                </label>
                                                <input type="file" class="form-control" id="team_logo" name="team_logo" required
                                                        accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                                <small class="form-text text-muted">Upload team logo (JPG, PNG, GIF, max 5MB)</small>
                                                <div id="logo-preview" class="mt-2" style="display: none;">
                                                    <img src="" alt="Logo Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="team_photo" class="form-label">
                                                    <i class="fas fa-camera me-2"></i>Team Photo *
                                                </label>
                                                <input type="file" class="form-control" id="team_photo" name="team_photo" required
                                                        accept="image/*" onchange="previewImage(this, 'photo-preview')">
                                                <small class="form-text text-muted">Upload team photo showing team members (JPG, PNG, GIF, max 5MB)</small>
                                                <div id="photo-preview" class="mt-2" style="display: none;">
                                                    <img src="" alt="Team Photo Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                                    <label class="form-check-label" for="consent">
                                        I agree to the <a href="../legal/privacy_policy.php" target="_blank">Privacy Policy</a> and consent to the processing of my personal data.
                                    </label>
                                </div>

                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-register text-white">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Registration
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">Already have an account? <a href="../auth/login.php">Login here</a></p>
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

        // Safe JSON bridge from PHP to JS for wards data
        const wards = <?php echo json_encode($wards, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;

        function filterWards() {
            const subCountySelect = document.getElementById('sub_county_id');
            const wardSelect = document.getElementById('ward_id');
            const selectedSubCounty = subCountySelect.value;

            // Clear ward selection
            wardSelect.innerHTML = '<option value="">Select Ward</option>';

            if (!selectedSubCounty) return;

            wards
                .filter(w => String(w.sub_county_id) === String(selectedSubCounty))
                .forEach(w => {
                    const option = document.createElement('option');
                    option.value = w.id;
                    option.textContent = w.name;
                    option.setAttribute('data-sub-county', w.sub_county_id);
                    wardSelect.appendChild(option);
                });
        }
    </script>
</body>
</html>