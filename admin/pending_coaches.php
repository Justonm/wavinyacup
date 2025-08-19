<?php
// admin/pending_coaches.php - Admin page for approving/rejecting coach registrations

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

// Check if user is logged in and has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();
$error = '';
$success = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $registration_id = (int)($_POST['registration_id'] ?? 0);
    $rejection_reason = sanitize_input($_POST['rejection_reason'] ?? '');
    
    if ($action === 'approve' && $registration_id > 0) {
        try {
            $db->beginTransaction();
            
            // Get registration details
            $registration = $db->fetchRow("
                SELECT cr.*, u.first_name, u.last_name, u.email, u.temp_password, c.license_number, c.license_type
                FROM coach_registrations cr
                JOIN users u ON cr.user_id = u.id
                JOIN coaches c ON c.user_id = u.id
                WHERE cr.id = ? AND cr.status = 'pending'
            ", [$registration_id]);
            
            if (!$registration) {
                throw new Exception('Registration not found or already processed.');
            }
            
            // Create team
            $ward = $db->fetchRow("SELECT * FROM wards WHERE id = ?", [$registration['ward_id']]);
            $team_code = generate_team_code($ward['code']);
            
            $db->query("
                INSERT INTO teams (name, ward_id, coach_id, team_code, status) 
                VALUES (?, ?, ?, ?, 'active')
            ", [$registration['team_name'], $registration['ward_id'], $registration['user_id'], $team_code]);
            
            $team_id = $db->lastInsertId();
            
            // Update coach with team assignment
            $db->query("UPDATE coaches SET team_id = ? WHERE user_id = ?", [$team_id, $registration['user_id']]);
            
            // Update user approval status
            $db->query("
                UPDATE users SET approval_status = 'approved', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ", [$user['id'], $registration['user_id']]);
            
            // Update registration status
            $db->query("
                UPDATE coach_registrations SET status = 'approved', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ", [$user['id'], $registration_id]);
            
            $db->commit();
            
            // Send approval email with enhanced formatting
            $email_subject = "🎉 Coach Registration Approved - Welcome to " . APP_NAME;
            $email_body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8f9fa;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                        <h1 style='margin: 0; font-size: 28px;'>🎉 Registration Approved!</h1>
                        <p style='margin: 10px 0 0 0; font-size: 16px;'>Welcome to " . APP_NAME . "</p>
                    </div>
                    
                    <div style='padding: 30px; background-color: white;'>
                        <p style='font-size: 18px; color: #333;'>Dear <strong>{$registration['first_name']} {$registration['last_name']}</strong>,</p>
                        
                        <p style='color: #666; line-height: 1.6;'>Congratulations! Your coach registration has been approved. You can now access your coach dashboard and start managing your team.</p>
                        
                        <div style='background-color: #e8f5e8; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0;'>
                            <h3 style='color: #155724; margin-top: 0;'>🔐 Your Login Credentials</h3>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr><td style='padding: 8px 0; font-weight: bold; color: #333;'>Email:</td><td style='padding: 8px 0; color: #666;'>{$registration['email']}</td></tr>
                                <tr><td style='padding: 8px 0; font-weight: bold; color: #333;'>Password:</td><td style='padding: 8px 0; color: #666; font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px;'>{$registration['temp_password']}</td></tr>
                                <tr><td style='padding: 8px 0; font-weight: bold; color: #333;'>Team Name:</td><td style='padding: 8px 0; color: #666;'>{$registration['team_name']}</td></tr>
                                <tr><td style='padding: 8px 0; font-weight: bold; color: #333;'>Team Code:</td><td style='padding: 8px 0; color: #666; font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px;'>{$team_code}</td></tr>
                            </table>
                        </div>
                        
                        <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                            <p style='margin: 0; color: #856404;'><strong>⚠️ Important:</strong> Please change your password immediately after logging in for security purposes.</p>
                        </div>
                        
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='" . APP_URL . "/auth/login.php' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>🚀 Login to Dashboard</a>
                        </div>
                        
                        <div style='background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0;'>
                            <h4 style='color: #1976d2; margin-top: 0;'>📋 What's Next?</h4>
                            <ul style='color: #666; line-height: 1.6;'>
                                <li>Login to your coach dashboard</li>
                                <li>Update your profile information</li>
                                <li>Add up to 22 players to your team</li>
                                <li>Manage your team for upcoming tournaments</li>
                            </ul>
                        </div>
                        
                        <p style='color: #666; line-height: 1.6;'>If you have any questions or need assistance, please don't hesitate to contact us.</p>
                        
                        <div style='border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px; text-align: center; color: #888;'>
                            <p style='margin: 0;'>Best regards,<br><strong>" . APP_NAME . " Administration Team</strong></p>
                            <p style='margin: 10px 0 0 0; font-size: 14px;'>Email: " . APP_EMAIL . "</p>
                        </div>
                    </div>
                </div>
            ";
            
            if (send_email($registration['email'], $email_subject, $email_body)) {
                log_activity($user['id'], 'coach_approved', "Approved coach registration: {$registration['first_name']} {$registration['last_name']}");
                $success = "Coach registration approved and email sent successfully!";
            } else {
                log_activity($user['id'], 'coach_approved', "Approved coach registration: {$registration['first_name']} {$registration['last_name']} (email failed)");
                $success = "Coach registration approved, but email failed to send.";
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to approve registration: ' . $e->getMessage();
        }
        
    } elseif ($action === 'reject' && $registration_id > 0) {
        if (empty($rejection_reason)) {
            $error = 'Rejection reason is required.';
        } else {
            try {
                $db->beginTransaction();
                
                // Get registration details
                $registration = $db->fetchRow("
                    SELECT cr.*, u.first_name, u.last_name, u.email
                    FROM coach_registrations cr
                    JOIN users u ON cr.user_id = u.id
                    WHERE cr.id = ? AND cr.status = 'pending'
                ", [$registration_id]);
                
                if (!$registration) {
                    throw new Exception('Registration not found or already processed.');
                }
                
                // Update user status
                $db->query("
                    UPDATE users SET approval_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? 
                    WHERE id = ?
                ", [$user['id'], $rejection_reason, $registration['user_id']]);
                
                // Update registration status
                $db->query("
                    UPDATE coach_registrations SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? 
                    WHERE id = ?
                ", [$user['id'], $rejection_reason, $registration_id]);
                
                $db->commit();
                
                // Send rejection email
                $email_subject = "Coach Registration Status - " . APP_NAME;
                $email_body = "
                    <h2>Registration Update</h2>
                    <p>Dear {$registration['first_name']} {$registration['last_name']},</p>
                    <p>We regret to inform you that your coach registration has not been approved at this time.</p>
                    <p><strong>Reason:</strong> {$rejection_reason}</p>
                    <p>You may reapply or contact us for more information.</p>
                    <p>Best regards,<br>" . APP_NAME . " Team</p>
                ";
                
                send_email($registration['email'], $email_subject, $email_body);
                log_activity($user['id'], 'coach_rejected', "Rejected coach registration: {$registration['first_name']} {$registration['last_name']}");
                $success = "Coach registration rejected successfully!";
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to reject registration: ' . $e->getMessage();
            }
        }
    }
}

// Get pending registrations
$pending_registrations = $db->fetchAll("
    SELECT cr.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.created_at as user_created,
           c.license_number, c.license_type, c.experience_years, c.specialization,
           w.name as ward_name, sc.name as sub_county_name
    FROM coach_registrations cr
    JOIN users u ON cr.user_id = u.id
    JOIN coaches c ON c.user_id = u.id
    JOIN wards w ON cr.ward_id = w.id
    JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE cr.status = 'pending'
    ORDER BY cr.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Coach Approvals - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        .registration-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .registration-card:hover {
            transform: translateY(-3px);
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            border: none;
            color: white;
        }
        .badge-pending {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="main-container">
                    <!-- Header -->
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="fas fa-user-check me-3"></i>Pending Coach Approvals</h2>
                                <p class="mb-0">Review and approve coach registrations</p>
                            </div>
                            <div>
                                <a href="dashboard.php" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($pending_registrations)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h3>No Pending Registrations</h3>
                                <p class="text-muted">All coach registrations have been processed.</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <h5><i class="fas fa-clock me-2"></i>Pending Registrations (<?php echo count($pending_registrations); ?>)</h5>
                            </div>

                            <?php foreach ($pending_registrations as $registration): ?>
                                <div class="registration-card">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>
                                                        <span class="badge badge-pending ms-2">Pending</span>
                                                    </h5>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-calendar me-1"></i>Applied: <?php echo date('M j, Y g:i A', strtotime($registration['user_created'])); ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-section">
                                                        <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($registration['email']); ?></p>
                                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($registration['phone'] ?: 'Not provided'); ?></p>
                                                        <p class="mb-0"><strong>ID Number:</strong> <?php echo htmlspecialchars($registration['id_number'] ?: 'Not provided'); ?></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-section">
                                                        <h6><i class="fas fa-certificate me-2"></i>Coaching Credentials</h6>
                                                        <p class="mb-1"><strong>License:</strong> <?php echo htmlspecialchars($registration['license_number']); ?></p>
                                                        <p class="mb-1"><strong>Type:</strong> <?php echo htmlspecialchars($registration['license_type']); ?></p>
                                                        <p class="mb-0"><strong>Experience:</strong> <?php echo htmlspecialchars($registration['experience_years']); ?> years</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="info-section">
                                                <h6><i class="fas fa-users me-2"></i>Team Information</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Team Name:</strong> <?php echo htmlspecialchars($registration['team_name']); ?></p>
                                                        <p class="mb-0"><strong>Ward:</strong> <?php echo htmlspecialchars($registration['ward_name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Sub-County:</strong> <?php echo htmlspecialchars($registration['sub_county_name']); ?></p>
                                                        <?php if ($registration['specialization']): ?>
                                                            <p class="mb-0"><strong>Specialization:</strong> <?php echo htmlspecialchars($registration['specialization']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="d-grid gap-2">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    <button type="button" class="btn btn-approve" onclick="confirmApproval('<?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>', '<?php echo htmlspecialchars($registration['team_name']); ?>', this.form)">
                                                        <i class="fas fa-check me-2"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    <button type="button" class="btn btn-reject" onclick="confirmRejection('<?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>', this.form)">
                                                        <i class="fas fa-times me-2"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal<?php echo $registration['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Approve Registration</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to approve the registration for <strong><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></strong>?</p>
                                                <p>This will:</p>
                                                <ul>
                                                    <li>Create team "<?php echo htmlspecialchars($registration['team_name']); ?>"</li>
                                                    <li>Assign the coach to the team</li>
                                                    <li>Send login credentials via email</li>
                                                    <li>Activate the coach account</li>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    <button type="submit" class="btn btn-approve">
                                                        <i class="fas fa-check me-2"></i>Approve
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?php echo $registration['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Registration</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    
                                                    <p>Are you sure you want to reject the registration for <strong><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></strong>?</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="rejection_reason<?php echo $registration['id']; ?>" class="form-label">Rejection Reason *</label>
                                                        <textarea class="form-control" id="rejection_reason<?php echo $registration['id']; ?>" name="rejection_reason" rows="3" required 
                                                                  placeholder="Please provide a reason for rejection..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-reject">
                                                        <i class="fas fa-times me-2"></i>Reject
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple confirmation functions
        function confirmApproval(coachName, teamName, form) {
            var message = "Are you sure you want to approve the registration for " + coachName + "?\n\n";
            message += "This will:\n";
            message += "• Create team \"" + teamName + "\"\n";
            message += "• Assign the coach to the team\n";
            message += "• Send login credentials via email\n";
            message += "• Activate the coach account";
            
            if (confirm(message)) {
                form.submit();
            }
        }
        
        function confirmRejection(coachName, form) {
            var reason = prompt("Please provide a reason for rejecting " + coachName + "'s registration:");
            
            if (reason !== null && reason.trim() !== "") {
                // Add the rejection reason to the form
                var reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'rejection_reason';
                reasonInput.value = reason.trim();
                form.appendChild(reasonInput);
                
                form.submit();
            } else if (reason !== null) {
                alert("Rejection reason is required.");
            }
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    if (alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                        alert.style.transition = 'opacity 0.5s';
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.remove();
                        }, 500);
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html>
