<?php
// coaches/self_register.php - Coach Self Registration Page

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

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
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $id_number = sanitize_input($_POST['id_number'] ?? '');
    $license_number = sanitize_input($_POST['license_number'] ?? '');
    $license_type = $_POST['license_type'] ?? '';
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $team_name = sanitize_input($_POST['team_name'] ?? '');
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    $specialization = sanitize_input($_POST['specialization'] ?? '');
    $certifications = sanitize_input($_POST['certifications'] ?? '');
    
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

                    // Generate temporary password
                    $temp_password = 'coach' . rand(1000, 9999);
                    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                    
                    // Create username
                    $username = strtolower(str_replace(' ', '', $first_name) . '.' . str_replace(' ', '', $last_name) . '.' . time());
                    
                    // 1. Create user account with pending status
                    $db->query("
                        INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number, approval_status, temp_password) 
                        VALUES (?, ?, ?, 'coach', ?, ?, ?, ?, 'pending', ?)
                    ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number, $temp_password]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // 2. Create coach profile
                    $db->query("
                        INSERT INTO coaches (user_id, license_number, license_type, experience_years, specialization, certifications) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ", [$user_id, $license_number, $license_type, $experience_years, $specialization, $certifications]);
                    
                    // 3. Create coach registration record
                    $db->query("
                        INSERT INTO coach_registrations (user_id, team_name, ward_id) 
                        VALUES (?, ?, ?)
                    ", [$user_id, $team_name, $ward_id]);
                    
                    $db->commit();
                    
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
                    
                    send_email(APP_EMAIL, $admin_email_subject, $admin_email_body);
                    
                    $success = "Registration submitted successfully! Your application is pending admin approval. You will receive an email with your login credentials once approved.";
                    
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Registration - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .registration-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .form-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="registration-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus me-3"></i>Coach Registration</h2>
                        <p class="mb-0">Register as a coach and create your team</p>
                    </div>
                    
                    <div class="card-body p-4">
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
                                <!-- Personal Information -->
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

                                <!-- Coaching Credentials -->
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

                                <!-- Team Information -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-users me-2"></i>Team Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="team_name" class="form-label">Team Name *</label>
                                                <input type="text" class="form-control" id="team_name" name="team_name" required 
                                                       placeholder="Enter your team name" value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="ward_id" class="form-label">Ward *</label>
                                                <select class="form-control" id="ward_id" name="ward_id" required>
                                                    <option value="">Select Ward</option>
                                                    <?php foreach ($wards as $ward): ?>
                                                        <option value="<?php echo $ward['id']; ?>" <?php echo ($_POST['ward_id'] ?? '') == $ward['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($ward['name']); ?> (<?php echo htmlspecialchars($ward['sub_county_name']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
    </script>
</body>
</html>
