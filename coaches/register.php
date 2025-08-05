<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/image_upload.php';

// Check if user is logged in and has permission
if (!is_logged_in() || !has_permission('manage_coaches')) {
    redirect('../auth/login.php');
}

$user = get_current_user_data();
$db = db();
$error = '';
$success = '';

// Get teams and wards for the form
$teams = $db->fetchAll("
    SELECT t.*, w.name as ward_name 
    FROM teams t 
    JOIN wards w ON t.ward_id = w.id 
    WHERE t.status = 'active' 
    ORDER BY t.name
");

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
    $team_id = (int)($_POST['team_id'] ?? 0);
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    
    // Validation
    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required.';
    } elseif (!empty($email) && !validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($phone) && !validate_phone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (empty($license_number)) {
        $error = 'License number is required.';
    } elseif (empty($license_type)) {
        $error = 'Please select a license type.';
    } elseif ($experience_years < 0 || $experience_years > 50) {
        $error = 'Experience years must be between 0 and 50.';
    } else {
        // Handle image upload
        $coach_image = null;
        if (isset($_FILES['coach_image']) && $_FILES['coach_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['coach_image'], 'coach', 'photo');
            if (!$upload_result['success']) {
                $error = 'Image upload failed: ' . $upload_result['error'];
            } else {
                $coach_image = $upload_result['path'];
            }
        }
        
        if (empty($error)) {
            try {
                // Create user account for coach
                $username = strtolower($first_name . '.' . $last_name . '.' . time());
                $password_hash = password_hash('coach123', PASSWORD_DEFAULT); // Default password
                
                $db->query("
                    INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number) 
                    VALUES (?, ?, ?, 'coach', ?, ?, ?, ?)
                ", [$username, $email, $password_hash, $first_name, $last_name, $phone, $id_number]);
                
                $user_id = $db->lastInsertId();
                
                // Create coach profile
                $db->query("
                    INSERT INTO coaches (user_id, team_id, ward_id, license_number, license_type, experience_years, coach_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [$user_id, $team_id, $ward_id, $license_number, $license_type, $experience_years, $coach_image]);
                
                $coach_id = $db->lastInsertId();
                
                log_activity($user['id'], 'coach_registration', "Registered coach: $first_name $last_name");
                $success = "Coach '$first_name $last_name' registered successfully! Username: $username, Password: coach123";
                
            } catch (Exception $e) {
                // Delete uploaded image if database insert failed
                if ($coach_image) {
                    delete_image($coach_image);
                }
                $error = 'Failed to register coach. Please try again.';
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
    <title>Register Coach - <?php echo APP_NAME; ?></title>
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
                        <h2><i class="fas fa-chalkboard-teacher me-2"></i>Register New Coach</h2>
                        <p class="text-muted">Add a new coach to the system</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license_number" class="form-label">License Number *</label>
                                    <input type="text" class="form-control" id="license_number" name="license_number" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license_type" class="form-label">License Type *</label>
                                    <select class="form-control" id="license_type" name="license_type" required>
                                        <option value="">Select License Type</option>
                                        <option value="UEFA_A">UEFA A</option>
                                        <option value="UEFA_B">UEFA B</option>
                                        <option value="UEFA_C">UEFA C</option>
                                        <option value="CAF_A">CAF A</option>
                                        <option value="CAF_B">CAF B</option>
                                        <option value="CAF_C">CAF C</option>
                                        <option value="FIFA">FIFA</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="experience_years" class="form-label">Years of Experience</label>
                                    <input type="number" class="form-control" id="experience_years" name="experience_years" min="0" max="50" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="team_id" class="form-label">Assign to Team (Optional)</label>
                                    <select class="form-control" id="team_id" name="team_id">
                                        <option value="0">No Team Assignment</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id']; ?>">
                                                <?php echo htmlspecialchars($team['name']); ?> (<?php echo htmlspecialchars($team['ward_name']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ward_id" class="form-label">Ward</label>
                                    <select class="form-control" id="ward_id" name="ward_id">
                                        <option value="0">Select Ward</option>
                                        <?php foreach ($wards as $ward): ?>
                                            <option value="<?php echo $ward['id']; ?>">
                                                <?php echo htmlspecialchars($ward['name']); ?> (<?php echo htmlspecialchars($ward['sub_county_name']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="coach_image" class="form-label">
                                        <i class="fas fa-camera me-2"></i>Coach Photo
                                    </label>
                                    <input type="file" class="form-control" id="coach_image" name="coach_image" 
                                           accept="image/*" onchange="previewImage(this, 'coach-preview')">
                                    <small class="form-text text-muted">Upload coach photo (JPG, PNG, GIF, max 5MB)</small>
                                    <div id="coach-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="Coach Preview" class="img-thumbnail" style="max-width: 150px; max-height: 200px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../admin/coaches.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Coaches
                            </a>
                            <button type="submit" class="btn btn-register text-white">
                                <i class="fas fa-save me-2"></i>Register Coach
                            </button>
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