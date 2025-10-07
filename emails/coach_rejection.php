<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wavinya Cup Registration Status</title>
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
            background: linear-gradient(135deg, #D32F2F 0%, #B71C1C 100%);
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
        .rejection-reason {
            background-color: #f9f9f9;
            border-left: 4px solid #D32F2F;
            padding: 15px;
            margin: 20px 0;
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
            <h1>Wavinya Cup Registration Status</h1>
        </div>
        <div class="content">
            <h2>Registration Update</h2>
            <p>Dear <?php echo htmlspecialchars($full_name); ?>,</p>
            <p>Thank you for your interest in the Wavinya Cup. After careful review, we regret to inform you that your coach registration could not be approved at this time.</p>
            <p><strong>Reason for rejection:</strong></p>
            <div class="rejection-reason">
                <p><?php echo nl2br(htmlspecialchars($rejection_reason)); ?></p>
            </div>
            <p>If you believe this is an error or wish to provide more information, please contact our support team.</p>
            <p>Best regards,<br>The Wavinya Cup Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Wavinya Cup. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
