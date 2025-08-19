<?php
/**
 * User Login Page
 * * Handles user authentication, session management, and redirection based on user roles.
 */

// Include necessary files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Redirect if the user is already logged in
if (is_logged_in()) {
    redirect('../index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Find the user by email
            $user = db()->fetchRow("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1", [$email]);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if the user's account is approved (only for coaches)
                if ($user['role'] === 'coach' && isset($user['approval_status']) && $user['approval_status'] !== 'approved') {
                    if ($user['approval_status'] === 'pending') {
                        $error = 'Your coach registration is pending admin approval. You will receive an email once approved.';
                    } elseif ($user['approval_status'] === 'rejected') {
                        $error = 'Your coach registration was rejected. Please contact the administrator for more information.';
                    } else {
                        $error = 'Your account is not approved. Please contact the administrator.';
                    }
                } else {
                    // Password and approval status are correct, create the session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role']; // Use 'user_role' to be consistent

                    // Log the successful login attempt
                    log_activity($user['id'], 'login', 'User logged in successfully');
                    
                    // Update last login time
                    db()->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

                    // Redirect based on the user's role
                    switch ($user['role']) {
                        case 'admin':
                        case 'county_admin':
                        case 'sub_county_admin':
                        case 'ward_admin':
                            redirect('../admin/dashboard.php');
                            break;
                        case 'coach':
                            redirect('../coach/dashboard.php');
                            break;
                        case 'captain':
                            redirect('../captain/dashboard.php');
                            break;
                        case 'player':
                            redirect('../player/dashboard.php');
                            break;
                        default:
                            redirect('../index.php');
                            break;
                    }
                }
            } else {
                $error = 'Invalid email or password.';
                // Log failed login attempt
                // Note: It's better to log with an IP address if the user doesn't exist
                log_activity(null, 'failed_login', 'Invalid credentials for email: ' . $email);
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please contact administrator.';
            // Log the detailed database error
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d47a1, #b71c1c); /* blue to red from logo */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .login-header img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #0d47a1;
            box-shadow: 0 0 0 0.2rem rgba(13, 71, 161, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            color: white;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 71, 161, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="../assets/images/logo.png" alt="Governor Wavinya Cup Logo" style="width: 200px"height: 200px;">
            <h4 class="mb-0">Governor Wavinya Cup</h4>
            <small>Team Registration System</small>
        </div>
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted mb-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Contact the system administrator for login access.
                    </small>
                </p>
                <p class="mb-0">
                    <a href="../coaches/self_register.php" class="text-decoration-none">
                        <i class="fas fa-user-plus me-1"></i>Register as Coach
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>