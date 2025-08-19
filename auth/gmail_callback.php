<?php
/**
 * Gmail OAuth2 Callback Handler
 * Processes the OAuth2 callback from Google
 */

require_once __DIR__ . '/gmail_oauth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

try {
    // Check for authorization code
    if (!isset($_GET['code'])) {
        throw new Exception('Authorization code not received');
    }
    
    // Check for state parameter
    if (!isset($_GET['state'])) {
        throw new Exception('State parameter missing');
    }
    
    // Initialize OAuth handler
    $oauth = new GmailOAuth();
    
    // Handle the callback
    $user_info = $oauth->handleCallback($_GET['code'], $_GET['state']);
    
    // Redirect to admin dashboard
    header('Location: ../admin/dashboard.php');
    exit;
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Gmail OAuth callback error: " . $error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Error - Governor Wavinya Cup</title>
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
        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="mb-4">
            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
        </div>
        <h3 class="text-danger mb-3">Authentication Failed</h3>
        <p class="text-muted mb-4"><?= htmlspecialchars($error) ?></p>
        <a href="admin_login.php" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Login
        </a>
    </div>
</body>
</html>
