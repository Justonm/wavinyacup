<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$db = db();
$error = '';
$success = '';

// Check if a user is already logged in (only if not processing a form submission)
if (is_logged_in() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $user_role = get_user_role();
    if ($user_role === 'coach') {
        redirect('../coach/dashboard.php');
    } else {
        redirect_to_dashboard($user_role);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Fetch user data from the database
        $user = $db->fetchRow("SELECT * FROM users WHERE username = ? AND role = 'coach'", [$username]);

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Check if account is approved
                if ($user['approval_status'] === 'approved') {
                    // Set session variables and redirect to coach dashboard
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];

                    log_activity($user['id'], 'login', 'Coach logged in successfully.');
                    redirect('../coach/dashboard.php');
                } else {
                    $error = 'Your account is still pending approval. Please wait for an administrator to activate your account.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Coach Login'; include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body class="centered-card">
    <div class="text-center mb-4">
        <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
        <h4 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h4>
    </div>
    <div class="login-card">
        <div class="login-header">
            <h4 class="mb-0 text-white"><i class="fas fa-chalkboard-teacher me-2"></i>Coach Login</h4>
        </div>
        <div class="login-body">
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label text-white"><i class="fas fa-user me-2"></i>Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label text-white"><i class="fas fa-lock me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-custom btn-lg"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
            </div>
        </form>
            <div class="text-center mt-3">
                <a href="../coaches/self_register.php" class="text-white">Register as a new coach</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>