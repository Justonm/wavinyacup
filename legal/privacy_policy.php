<?php
require_once dirname(__DIR__) . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body class="registration-page">
    <div class="container py-5">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
            <h4 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h4>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="registration-card">
                    <div class="registration-card-header text-center">
                        <h2 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h2>
                    </div>
                    <div class="registration-card-body">
                        <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>

                        <h5 class="mt-4">1. Introduction</h5>
                        <p>Welcome to the Governor Wavinya Cup 3rd Edition registration system. We are committed to protecting your personal data and respecting your privacy. This policy outlines how we collect, use, store, and protect your information.</p>

                        <h5 class="mt-4">2. Data We Collect</h5>
                        <p>We collect the following personal data during registration:</p>
                        <ul>
                            <li><strong>Players, Coaches, and Captains:</strong> Full name, date of birth, ID number, phone number, email address, and personal photographs.</li>
                            <li><strong>Team Information:</strong> Team name, ward, home ground, and other related details.</li>
                        </ul>

                        <h5 class="mt-4">3. How We Use Your Data</h5>
                        <p>Your data is used for the following purposes:</p>
                        <ul>
                            <li>To manage team and player registration for the tournament.</li>
                            <li>To verify the identity and eligibility of participants.</li>
                            <li>To communicate important tournament information.</li>
                            <li>To organize fixtures and manage tournament logistics.</li>
                            <li>For disciplinary purposes, if necessary.</li>
                        </ul>

                        <h5 class="mt-4">4. Data Storage and Security</h5>
                        <p>We implement strong security measures to protect your data from unauthorized access, alteration, or disclosure. Data is stored on secure servers, and access is restricted to authorized personnel only.</p>

                        <h5 class="mt-4">5. Data Sharing</h5>
                        <p>We do not sell or rent your personal data. We may share your information with:</p>
                        <ul>
                            <li>Tournament officials for verification and management purposes.</li>
                            <li>Official media partners for promotional activities (e.g., announcing team lists), but only with necessary discretion.</li>
                        </ul>

                        <h5 class="mt-4">6. Your Rights</h5>
                        <p>Under data protection law, you have rights including:</p>
                        <ul>
                            <li><strong>Your right of access</strong> - You have the right to ask us for copies of your personal information.</li>
                            <li><strong>Your right to rectification</strong> - You have the right to ask us to rectify information you think is inaccurate.</li>
                            <li><strong>Your right to erasure</strong> - You have the right to ask us to erase your personal information in certain circumstances.</li>
                        </ul>
                        <p>To exercise these rights, please contact us at [Your Contact Email/Phone].</p>

                        <h5 class="mt-4">7. Consent</h5>
                        <p>By registering, you consent to the collection and use of your personal data as described in this policy. You can withdraw your consent at any time by contacting us, though this may affect your eligibility to participate in the tournament.</p>

                        <div class="text-center mt-4">
                            <button onclick="window.close();" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
