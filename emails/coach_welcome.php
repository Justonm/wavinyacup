<?php
// Construct the login URL dynamically.
// Assumes the site is hosted at a base URL defined in config.php or determined dynamically.
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$login_path = '/auth/coach_login.php'; // Path to the coach login page
$login_url = $base_url . $login_path;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Wavinya Cup!</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
            background-color: #f4f4f7;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .content p {
            margin: 0 0 15px;
        }
        .credentials {
            background-color: #f9f9f9;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
        }
        .credentials strong {
            display: inline-block;
            min-width: 100px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Wavinya Cup!</h1>
        </div>
        <div class="content">
            <h2>Your Registration is Approved!</h2>
            <p>Dear <?php echo htmlspecialchars($full_name); ?>,</p>
            <p>Congratulations! Your registration as a coach for the Wavinya Cup has been approved. Your team, <strong><?php echo htmlspecialchars($team_name); ?></strong> (Team Code: <strong><?php echo htmlspecialchars($team_code); ?></strong>), has been created.</p>
            <p>Please use the following credentials to log in to your dashboard and start managing your team:</p>
            <div class="credentials">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Password:</strong> <?php echo htmlspecialchars($password); ?></p>
            </div>
            <p>We recommend you change your password after your first login for security reasons.</p>
            <div class="button-container">
                <a href="<?php echo htmlspecialchars($login_url); ?>" class="button">Login to Your Dashboard</a>
            </div>
            <p>Best regards,<br>The Wavinya Cup Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Wavinya Cup. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

