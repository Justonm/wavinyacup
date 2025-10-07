<?php
// public_index.php - Public landing page with coach registration

require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Team Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            padding: 3rem;
            margin: 2rem 0;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .logo {
            max-width: 150px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Hero Section -->
                <div class="hero-section text-center">
                    <img src="assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo" class="logo mb-4">
                    <h1 class="display-4 mb-3"><?php echo APP_NAME; ?></h1>
                    <p class="lead mb-4">Machakos County Team Registration System</p>
                    <p class="text-muted mb-4">Register your football team and manage players with our comprehensive system</p>
                    
                    <div class="row justify-content-center mb-4">
                        <div class="col-md-8">
                            <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                                <a href="coaches/self_register.php" class="btn btn-custom btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Register as Coach
                                </a>
                                <a href="auth/coach_login.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Coach Login
                                </a>
                                <a href="auth/admin_login.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-user-shield me-2"></i>Admin Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="row mb-5">
                    <div class="col-md-4 mb-4">
                        <div class="feature-card text-center">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h5>Team Management</h5>
                            <p class="text-muted">Register and manage your football team with up to 22 players per team</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card text-center">
                            <div class="mb-3">
                                <i class="fas fa-chalkboard-teacher fa-3x text-success"></i>
                            </div>
                            <h5>Coach Registration</h5>
                            <p class="text-muted">Self-register as a coach and create your team with admin approval</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card text-center">
                            <div class="mb-3">
                                <i class="fas fa-shield-alt fa-3x text-warning"></i>
                            </div>
                            <h5>Secure System</h5>
                            <p class="text-muted">Role-based access control ensuring data security and proper management</p>
                        </div>
                    </div>
                </div>

                <!-- How it Works Section -->
                <div class="hero-section">
                    <h2 class="text-center mb-4">How Coach Registration Works</h2>
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="mb-3">
                                <span class="badge bg-primary rounded-circle p-3 fs-4">1</span>
                            </div>
                            <h6>Register</h6>
                            <p class="text-muted small">Fill out the coach registration form with your credentials and team name</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="mb-3">
                                <span class="badge bg-warning rounded-circle p-3 fs-4">2</span>
                            </div>
                            <h6>Wait for Approval</h6>
                            <p class="text-muted small">Admin reviews your application and credentials</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="mb-3">
                                <span class="badge bg-success rounded-circle p-3 fs-4">3</span>
                            </div>
                            <h6>Get Login Details</h6>
                            <p class="text-muted small">Receive email with login credentials once approved</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="mb-3">
                                <span class="badge bg-info rounded-circle p-3 fs-4">4</span>
                            </div>
                            <h6>Manage Team</h6>
                            <p class="text-muted small">Login and add up to 22 players to your team</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center py-4">
                    <p class="text-white-50 mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
