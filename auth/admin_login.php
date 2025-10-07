<?php
/**
 * Secure Admin Login Page with Gmail OAuth2
 * Enhanced security with Google authentication
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/gmail_oauth.php';


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
    <?php $page_title = 'Admin Login'; include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body class="login-page-new">
    <div class="container-fluid">
        <div class="row g-0">
            <!-- Left Panel: Visual Branding -->
            <div class="col-lg-7 d-none d-lg-block">
                <div class="login-visual-panel">
                    <div class="text-overlay">
                        <img src="../assets/images/logo.png" alt="Logo" class="login-logo mb-4">
                        <h1 class="text-white">Governor Wavinya Cup</h1>
                        <p class="text-white-50">Empowering Machakos County through sports.</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Login Form -->
            <div class="col-lg-5">
                <div class="login-form-panel">
                    <div class="login-form-wrapper">
                        <div class="text-center mb-5">
                            <h2 class="fw-bold">Admin Portal</h2>
                            <p class="text-muted">Secure access for authorized personnel.</p>
                        </div>

                        <a href="<?= htmlspecialchars($auth_url) ?>" class="btn btn-google w-100">
                            <i class="fab fa-google me-2"></i> Sign in with Google
                        </a>

                        <div class="security-info mt-5 text-center">
                            <i class="fas fa-shield-alt text-primary fa-2x mb-3"></i>
                            <h6 class="fw-bold">Enhanced Security</h6>
                            <p class="text-muted small">
                                This portal is protected by Google's robust security infrastructure, including 2-Factor Authentication and continuous monitoring.
                            </p>
                        </div>

                        <div class="text-center mt-4">
                            <a href="../index.php" class="text-decoration-none small">
                                <i class="fas fa-arrow-left me-1"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
