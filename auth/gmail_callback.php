<?php

/**
 * Gmail OAuth2 Callback Handler
 * Processes the OAuth2 callback from Google
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/gmail_oauth.php';


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
    
    $db = db();
    $email = $user_info['email'];

    // Check if the user is a whitelisted admin
        $whitelisted_admins = explode(',', $_ENV['ADMIN_EMAILS'] ?? '');
    if (!in_array($email, $whitelisted_admins)) {
        throw new Exception('Access Denied: Your account is not authorized for admin access.');
    }

    // Use a single query to insert the user if they don't exist, and update their role on duplicate email.
    // This is an 'upsert' operation.
    $username = explode('@', $email)[0];
    $db->query(
        'INSERT INTO users (username, email, first_name, last_name, role, approval_status, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role = ?, first_name = ?, last_name = ?, approval_status = ?, username = ?',
        [$username, $email, $user_info['given_name'], $user_info['family_name'], 'admin', 'approved', '', 'admin', $user_info['given_name'], $user_info['family_name'], 'approved', $username]
    );

    // Get the user ID and set it in the session
    $user = $db->fetchRow('SELECT id FROM users WHERE email = ?', [$email]);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
    } else {
        // This should never happen after an upsert, but as a safeguard:
        throw new Exception('Failed to retrieve user ID after provisioning.');
    }

    // Store user info in session for consistency
    $_SESSION['user_info'] = $user_info;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_method'] = 'google';

        // IMPORTANT: Explicitly close the session to ensure data is written before redirecting
    session_write_close();

    // Redirect to the admin dashboard
    redirect('../admin/dashboard.php');
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Gmail OAuth callback error: " . $error);

    // Clear any potentially problematic session data
    session_unset();
    session_destroy();
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
        <a href="/auth/admin_login.php" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Login
        </a>
    </div>
</body>
</html>
