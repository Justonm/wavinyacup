<?php
/**
 * Secure Admin Login Page with Gmail OAuth2
 * Enhanced security with Google authentication
 */

require_once __DIR__ . '/gmail_oauth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already authenticated
if (GmailOAuth::isValidAdminSession()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Initialize OAuth handler
$oauth = new GmailOAuth();
$auth_url = $oauth->getAuthUrl();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
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
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header img {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 2.5rem;
        }
        .google-btn {
            background: #4285f4;
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            font-weight: 600;
            width: 100%;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        .google-btn:hover {
            background: #357ae8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(66, 133, 244, 0.4);
            color: white;
            text-decoration: none;
        }
        .security-features {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        .security-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .security-item:last-child {
            margin-bottom: 0;
        }
        .security-icon {
            color: #28a745;
            margin-right: 10px;
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="../assets/images/logo.png" alt="Governor Wavinya Cup Logo">
            <h4 class="mb-0">Admin Access</h4>
            <small>Governor Wavinya Cup System</small>
        </div>
        <div class="login-body">
            <div class="text-center mb-4">
                <h5 class="text-dark mb-3">Secure Admin Login</h5>
                <p class="text-muted">
                    <i class="fas fa-shield-alt me-2"></i>
                    Enhanced security with Gmail authentication
                </p>
            </div>

            <a href="<?= htmlspecialchars($auth_url) ?>" class="google-btn">
                <i class="fab fa-google me-3"></i>
                Sign in with Gmail
            </a>

            <div class="security-features">
                <h6 class="text-dark mb-3">
                    <i class="fas fa-lock me-2"></i>Security Features
                </h6>
                <div class="security-item">
                    <i class="fas fa-check security-icon"></i>
                    <small class="text-muted">OAuth2 Authentication</small>
                </div>
                <div class="security-item">
                    <i class="fas fa-check security-icon"></i>
                    <small class="text-muted">2-Factor Authentication via Gmail</small>
                </div>
                <div class="security-item">
                    <i class="fas fa-check security-icon"></i>
                    <small class="text-muted">Session Timeout Protection</small>
                </div>
                <div class="security-item">
                    <i class="fas fa-check security-icon"></i>
                    <small class="text-muted">Authorized Email Verification</small>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted mb-0">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Only authorized Gmail accounts can access admin functions
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
