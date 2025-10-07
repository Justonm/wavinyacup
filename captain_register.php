<?php
// Corrected file paths for includes.
// The `captain_register.php` file is in the root, and the included files are in subdirectories.
// Therefore, we removed the `../` from the paths.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

// Start a session only if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = db();
$error = '';
$success = '';

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Sanitize and validate inputs
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $team_name = sanitize_input($_POST['team_name'] ?? '');
        $consent = isset($_POST['consent']);

        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($team_name)) {
            $error = 'All fields are required.';
        } elseif (!validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!empty($phone) && !validate_phone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (!$consent) {
            $error = 'You must agree to the privacy policy to register.';
        } else {
            // Check if email or team name already exists
            $user_exists = $db->fetchRow("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($user_exists) {
                $error = 'An account with this email already exists.';
            } else {
                $team_exists = $db->fetchRow("SELECT id FROM teams WHERE name = ? LIMIT 1", [$team_name]);
                if ($team_exists) {
                    $error = 'A team with this name has already been registered.';
                }
            }
        }
    }

    if (empty($error)) {
        try {
            // Generate a unique token for email verification
            $activation_token = bin2hex(random_bytes(32));
            $role = 'captain';
            $status = 'pending';

            $consent_timestamp = date('Y-m-d H:i:s');
            // Create a temporary user account with 'pending' status and NULL password
            $db->query("
                INSERT INTO users (first_name, last_name, email, phone, role, password_hash, approval_status, activation_token, consent_given_at)
                VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)
            ", [
                $first_name,
                $last_name,
                $email,
                $phone,
                $role,
                $status,
                $activation_token,
                $consent_timestamp
            ]);

            $user_id = $db->lastInsertId();

            // Create the team and link it to the captain
            $db->query("
                INSERT INTO teams (name, captain_user_id)
                VALUES (?, ?)
            ", [$team_name, $user_id]);

            // Construct the verification email
            $verification_link = APP_URL . "/auth/verify_captain.php?token=" . $activation_token;
            $subject = "Verify Your Captain Account for " . APP_NAME;
            $body = "Hi $first_name,<br><br>" .
                    "Thank you for registering your team, **" . htmlspecialchars($team_name) . "**, for the Wavinya Cup.<br><br>" .
                    "Please click the link below to verify your email address:<br>" .
                    "<a href=\"$verification_link\">Verify Email Address</a><br><br>" .
                    "Once verified, our administrator will review your application. You will receive another email with your login details once your account is approved.<br><br>" .
                    "Regards,<br>" .
                    "The " . APP_NAME . " Team";

            // Send the email
            send_email($email, $subject, $body);

            // Set the success message and clear the CSRF token to prevent reuse
            $success = "Registration successful! A verification email has been sent to your email address. Please check your inbox and spam folder to verify your account.";
            unset($_SESSION['csrf_token']);

        } catch (Exception $e) {
            // Log the error and display a generic message to the user
            error_log("Captain registration failed: " . $e->getMessage());
            $error = 'An unexpected error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain & Team Registration - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="registration-page">
    <div class="container">
        <div class="text-center mb-4">
            <img src="assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
            <h4 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h4>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="registration-card">
                    <div class="registration-card-header text-center">
                        <h2 class="mb-1"><i class="fas fa-users-cog me-2"></i>Captain & Team Registration</h2>
                        <p class="mb-0 text-light op-7">Register your team for the Wavinya Cup</p>
                    </div>
                    <div class="registration-card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label"><i class="fas fa-user me-2"></i>First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label"><i class="fas fa-user me-2"></i>Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label"><i class="fas fa-phone me-2"></i>Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label for="team_name" class="form-label"><i class="fas fa-shield-alt me-2"></i>Team Name</label>
                            <input type="text" class="form-control" id="team_name" name="team_name" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                            <label class="form-check-label" for="consent">
                                I agree to the <a href="legal/privacy_policy.php" target="_blank">Privacy Policy</a> and consent to the processing of my personal data.
                            </label>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check-circle me-2"></i>Register My Team</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>