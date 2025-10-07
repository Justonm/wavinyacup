<?php
// admin/pending_coaches.php - Admin page for approving/rejecting coach registrations

// -----------------------------------------------------------
// 1. INCLUDES & SESSION MANAGEMENT
// -----------------------------------------------------------
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../auth/gmail_oauth.php';

// Check if user has a valid admin session (OAuth or regular)
if (!GmailOAuth::isValidAdminSession() && (!is_logged_in() || !has_role('admin'))) {
    redirect('../auth/admin_login.php');
}

// Establish DB connection once.
$db = db();
if (!$db) {
    error_log('FATAL: Database connection failed in pending_coaches.php');
    die('A critical database error occurred. Please contact the administrator.');
}

// Get the current user *after* validating the session
// The session now reliably contains `user_id` for both login methods.
$user = null;
if (isset($_SESSION['user_id'])) {
    $db = db();
    $user = $db->fetchRow('SELECT * FROM users WHERE id = ? AND role = \'admin\'', [$_SESSION['user_id']]);
}

// CRITICAL: Ensure we have a valid admin user from our database.
if (!$user) {
    error_log('SECURITY: Valid session but no matching admin user found in database. Email: ' . ($_SESSION['user_email'] ?? 'N/A'));
    die('Access Denied. Your account is not authorized to view this page.');
}
$error = '';
$success = '';

// -----------------------------------------------------------
// 2. POST REQUEST HANDLING (AJAX)
// -----------------------------------------------------------
// Handle approval/rejection via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The `$user` variable is already fetched and validated before this block.
    // Now we can safely close the session to prevent blocking other requests.
    session_write_close();

    error_log('--- PENDING COACHES: POST request received ---');
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $action = $_POST['action'] ?? '';
    $registration_id = (int)($_POST['registration_id'] ?? 0);
    $rejection_reason = htmlspecialchars($_POST['rejection_reason'] ?? '');
    error_log('Action: ' . $action . ', Registration ID: ' . $registration_id . ', User ID: ' . ($user['id'] ?? 'N/A'));
    
    if ($action === 'approve' && $registration_id > 0) {
        $start_time = microtime(true);
        error_log("[TIMING] START: Approve action for registration {$registration_id}.");

        try {
            $db->beginTransaction();
            // Check if the registration is actually pending and get user details for email
            error_log("[TIMING] STEP 1: Fetching registration and user details...");
            $fetch_start = microtime(true);
            $registration = $db->fetchRow("
                SELECT cr.id, cr.team_name, cr.ward_id, u.id as user_id, u.first_name, u.last_name, u.email
                FROM coach_registrations cr
                JOIN users u ON cr.user_id = u.id
                WHERE cr.id = ? AND cr.status = 'pending' FOR UPDATE"
            , [$registration_id]);
            $fetch_end = microtime(true);
            error_log("[TIMING] STEP 1 DONE in " . ($fetch_end - $fetch_start) . " seconds.");

            if (!$registration) {
                throw new Exception('Registration not found or already processed.');
            }

            // 1. Generate new credentials
            $raw_password = bin2hex(random_bytes(4)); // 8-character secure password
            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

            // Generate a unique username
            $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $registration['first_name']) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $registration['last_name']));
            $username = $base_username;
            $counter = 1;
            
            // Check for username uniqueness in the correct table
            while ($db->query("SELECT id FROM users WHERE username = ?", [$username])->fetch()) {
                $username = $base_username . $counter;
                $counter++;
            }

            // 2. Create the team first
            $team_name = $registration['team_name'];
            $ward_id = $registration['ward_id'];
            $team_code = generate_team_code($ward_id);

            $db->query("INSERT INTO teams (name, team_code, ward_id) VALUES (?, ?, ?)", [
                $team_name,
                $team_code,
                $ward_id
            ]);
            $new_team_id = $db->lastInsertId();

            // 3. Update user with new credentials and set as active
            $db->query("UPDATE users SET username = ?, password_hash = ?, approval_status = 'approved', role = 'coach' WHERE id = ?", [
                $username,
                $password_hash,
                $registration['user_id']
            ]);

            // 4. Update the coaches table with the new team ID
            $db->query("UPDATE coaches SET team_id = ? WHERE user_id = ?", [
                $new_team_id,
                $registration['user_id']
            ]);

            // 5. Update registration status
            $db->query("UPDATE coach_registrations SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?", [
                $user['id'],
                $registration_id
            ]);

            $db->commit();

            // 6. Queue approval email with new credentials
            $email_subject = "Welcome to " . APP_NAME . " - Your Registration is Approved!";
            $email_data = [
                'full_name' => $registration['first_name'] . ' ' . $registration['last_name'],
                'username' => $username,
                'password' => $raw_password,
                'team_name' => $team_name,
                'team_code' => $team_code
            ];
            $email_body = get_email_template('coach_welcome', $email_data);

            if (queue_email($registration['email'], $email_subject, $email_body)) {
                $success = "Coach registration approved. A welcome email with credentials has been queued.";
                log_activity($user['id'], 'coach_approved_credentials_sent', "Approved and queued credentials for coach ID {$registration_id}");
            } else {
                $success = "Coach registration approved, but the welcome email could not be queued. Please follow up manually.";
                error_log("CRITICAL: Failed to queue approval email for coach registration ID {$registration_id}");
            }
            if ($user && isset($user['id'])) {
                log_activity($user['id'], 'coach_approved', "Approved coach registration: ID {$registration_id}");
            } else {
                error_log('CRITICAL: Could not log coach approval activity because user or user ID was not found.');
            }

            if ($is_ajax) {
                error_log("[TIMING] STEP 3: Sending AJAX success response...");
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => $success, 'registration_id' => $registration_id]);
                $final_end_time = microtime(true);
                error_log("[TIMING] END: Total execution time: " . ($final_end_time - $start_time) . " seconds.");
                exit;
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            $error_end_time = microtime(true);
            error_log("[TIMING] ERROR: Total execution time before error: " . ($error_end_time - $start_time) . " seconds.");
            error_log('Failed to approve registration ' . $registration_id . '. Error: ' . $e->getMessage());
            $error = 'Failed to approve registration: ' . $e->getMessage();
            if ($is_ajax) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $error]);
                exit;
            }
        }
        
    } elseif ($action === 'reject' && $registration_id > 0) {
        if (empty($rejection_reason)) {
            error_log('Rejection failed: reason is empty.');
            $error = 'Rejection reason is required.';
            if ($is_ajax) {
                header('Content-Type: application/json');
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => $error]);
                exit;
            }
        } else {
            error_log('Attempting to reject registration ' . $registration_id);
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
                $db->query("UPDATE users SET approval_status = 'denied', rejection_reason = ? WHERE id = ?", [
                    $rejection_reason,
                    $registration['user_id']
                ]);
                
                // Update registration status
                $db->query("
                    UPDATE coach_registrations SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? 
                    WHERE id = ?
                ", [$user['id'], $rejection_reason, $registration_id]);
                
                $db->commit();
                
                // Send rejection email
                $email_subject = "Coach Registration Status - " . APP_NAME;
                $email_data = [
                    'full_name' => $registration['first_name'] . ' ' . $registration['last_name'],
                    'rejection_reason' => $rejection_reason
                ];
                $email_body = get_email_template('coach_rejection', $email_data);
                
                // Queue the rejection email instead of sending it directly.
                if ($user && isset($user['id'])) {
                    if (queue_email($registration['email'], $email_subject, $email_body)) {
                        log_activity($user['id'], 'coach_rejected', "Rejected and queued notification email for coach: {$registration['first_name']} {$registration['last_name']}");
                        $success = "Coach registration rejected! A notification email has been queued and will be sent shortly.";
                    } else {
                        log_activity($user['id'], 'coach_rejected_email_queue_failed', "Rejected coach registration but FAILED to queue notification email for: {$registration['first_name']} {$registration['last_name']}");
                        $success = "Coach registration rejected, but there was an error queueing the notification email. Please follow up if necessary.";
                    }
                } else {
                    error_log('CRITICAL: Could not log coach rejection because user or user ID was not found.');
                    $success = "Coach registration rejected, but a logging error occurred.";
                }

                if ($is_ajax) {
                    error_log("Rejection for registration {$registration_id} complete. Sending AJAX success response.");
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => $success, 'registration_id' => $registration_id]);
                    exit;
                }

            } catch (Exception $e) {
                $db->rollBack();
                error_log('Transaction rolled back for rejection of registration ' . $registration_id . '. Error: ' . $e->getMessage());
                $error = 'Failed to reject registration: ' . $e->getMessage();
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => $error]);
                    exit;
                }
            }
        }
    }
}

// -----------------------------------------------------------
// 3. GET REQUEST & PAGE RENDERING
// -----------------------------------------------------------
// Get pending registrations
$pending_registrations = $db->fetchAll("
    SELECT cr.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.created_at as user_created, u.approval_status,
            c.license_number, c.license_type, c.experience_years, c.specialization, c.coach_image,
            w.name as ward_name, sc.name as sub_county_name
    FROM coach_registrations cr
    LEFT JOIN users u ON cr.user_id = u.id
    LEFT JOIN coaches c ON u.id = c.user_id
    LEFT JOIN wards w ON cr.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE cr.status = 'pending' AND u.approval_status = 'pending'
    ORDER BY cr.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Pending Coach Approvals'; include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'sidebar.php'; ?>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-user-check me-3"></i>Pending Coach Approvals</h2>
                            <p class="text-muted">Review and approve coach registrations</p>
                        </div>
                    </div>

                    <div id="alert-container"></div>

                    <div id="no-pending-message-container" style="display: <?php echo empty($pending_registrations) ? 'block' : 'none'; ?>;">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h3>No Pending Registrations</h3>
                                <p class="text-muted">All coach registrations have been processed.</p>
                            </div>
                        </div>
                    </div>

                    <div id="registrations-list" style="display: <?php echo empty($pending_registrations) ? 'none' : 'block'; ?>;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Registrations (<span id="pending-count"><?php echo count($pending_registrations); ?></span>)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($pending_registrations as $registration): ?>
                                    <div class="card mb-3" id="registration-card-<?php echo $registration['id']; ?>">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-1">
                                                    <?php if ($registration['coach_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($registration['coach_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>" 
                                                             class="img-thumbnail rounded-circle" 
                                                             style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 60px; height: 60px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-5">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></h5>
                                                    <p class="mb-1"><strong>Team:</strong> <?php echo htmlspecialchars($registration['team_name']); ?></p>
                                                    <p class="text-muted mb-0"><i class="fas fa-calendar me-1"></i>Applied: <?php echo date('M j, Y g:i A', strtotime($registration['user_created'])); ?></p>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-success approve-btn" data-id="<?php echo $registration['id']; ?>" data-name="<?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>" data-team-name="<?php echo htmlspecialchars($registration['team_name']); ?>">
                                                            <i class="fas fa-check me-1"></i> Approve
                                                        </button>
                                                        <button class="btn btn-danger reject-btn" data-id="<?php echo $registration['id']; ?>" data-name="<?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>">
                                                            <i class="fas fa-times me-1"></i> Reject
                                                        </button>
                                                        <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?php echo $registration['id']; ?>" aria-expanded="false" aria-controls="details-<?php echo $registration['id']; ?>">
                                                            <i class="fas fa-info-circle me-1"></i> Details
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="collapse" id="details-<?php echo $registration['id']; ?>">
                                            <div class="card-footer bg-light">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                                                        <?php if ($registration['coach_image']): ?>
                                                            <div class="mb-2">
                                                                <img src="../<?php echo htmlspecialchars($registration['coach_image']); ?>" 
                                                                     alt="Coach Photo" 
                                                                     class="img-thumbnail" 
                                                                     style="width: 120px; height: 120px; object-fit: cover;">
                                                            </div>
                                                        <?php endif; ?>
                                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($registration['email']); ?></p>
                                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($registration['phone'] ?: 'N/A'); ?></p>
                                                        <p class="mb-0"><strong>ID Number:</strong> <?php echo htmlspecialchars($registration['id_number'] ?: 'N/A'); ?></p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <h6><i class="fas fa-map-marker-alt me-2"></i>Team & Location</h6>
                                                        <p class="mb-1"><strong>Team Name:</strong> <?php echo htmlspecialchars($registration['team_name']); ?></p>
                                                        <p class="mb-1"><strong>Sub-County:</strong> <?php echo htmlspecialchars($registration['sub_county_name']); ?></p>
                                                        <p class="mb-0"><strong>Ward:</strong> <?php echo htmlspecialchars($registration['ward_name']); ?></p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <h6><i class="fas fa-certificate me-2"></i>Coaching Credentials</h6>
                                                        <p class="mb-1"><strong>License:</strong> <?php echo htmlspecialchars($registration['license_number']); ?></p>
                                                        <p class="mb-1"><strong>Type:</strong> <?php echo htmlspecialchars($registration['license_type']); ?></p>
                                                        <p class="mb-1"><strong>Experience:</strong> <?php echo htmlspecialchars($registration['experience_years']); ?> years</p>
                                                        <?php if ($registration['specialization']): ?>
                                                            <p class="mb-0"><strong>Specialization:</strong> <?php echo htmlspecialchars($registration['specialization']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve the registration for <strong id="approve-name"></strong>?</p>
                    <p>This will:</p>
                    <ul>
                        <li>Create team "<strong id="approve-team-name"></strong>"</li>
                        <li>Assign the coach to the team</li>
                        <li>Send login credentials via email</li>
                        <li>Activate the coach account</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmApproveBtn">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectionForm">
                        <div class="mb-3">
                            <label for="rejection-reason" class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" id="rejection-reason" rows="3" required></textarea>
                            <div class="invalid-feedback">
                                A reason is required for rejection.
                            </div>
                        </div>
                        <p>Are you sure you want to reject the registration for <strong id="reject-name"></strong>?</p>
                        <input type="hidden" id="reject-id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRejectBtn">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/pending_coaches.js" defer></script>
</body>
</html>