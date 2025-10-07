<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$db = db();
$error = '';

// Check if a user is already logged in
if (is_logged_in()) {
    // Redirect to the appropriate dashboard based on role
    $user_data = get_current_user_data();
    if ($user_data['role'] === 'admin') {
        redirect('../admin/dashboard.php');
    } elseif ($user_data['role'] === 'captain') {
        redirect('../captain/dashboard.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        // Fetch user data from the database
        $user = $db->fetchRow("SELECT * FROM users WHERE email = ? AND role = 'captain'", [$email]);

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Check if account is approved
                if ($user['approval_status'] === 'approved') {
                    // Set session variables and redirect to captain dashboard
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_team_id'] = $db->fetchColumn("SELECT id FROM teams WHERE captain_user_id = ?", [$user['id']]);

                    log_activity($user['id'], 'login', 'Captain logged in successfully.');
                    redirect('../captain/dashboard.php');
                } else {
                    $error = 'Your account is still pending approval. Please wait for an administrator to activate your account.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body class="centered-card">
    <div class="text-center mb-4">
        <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
        <h4 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h4>
    </div>
    <div class="login-card">
        <div class="login-header">
            <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Captain Login</h4>
        </div>
        <div class="login-body">
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
            </div>
        </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>