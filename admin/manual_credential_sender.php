<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../auth/gmail_oauth.php';

// Security check: ensure user is a logged-in admin
if (!GmailOAuth::isValidAdminSession() && (!is_logged_in() || !has_role('admin'))) {
    redirect('../auth/admin_login.php');
}

$db = db();
$error = '';
$success = '';

// Handle POST request for sending credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_credentials') {
    session_write_close(); // Close session to avoid blocking
    header('Content-Type: application/json');

    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Fetch user and team details
        $coach_details = $db->fetchRow("
            SELECT u.id, u.first_name, u.last_name, u.email, u.username, t.name as team_name, t.team_code
            FROM users u
            JOIN coaches c ON u.id = c.user_id
            LEFT JOIN teams t ON c.team_id = t.id
            WHERE u.id = ? AND u.role = 'coach'
        ", [$user_id]);

        if (!$coach_details) {
            throw new Exception('Coach not found or user is not a coach.');
        }

        // Generate new credentials
        $raw_password = bin2hex(random_bytes(4)); // 8-character password
        $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

        // Update user's password
        $db->query('UPDATE users SET password_hash = ? WHERE id = ?', [$password_hash, $user_id]);

        // Prepare email
        $email_subject = "Your Login Credentials for " . APP_NAME;
        $email_data = [
            'full_name' => $coach_details['first_name'] . ' ' . $coach_details['last_name'],
            'username' => $coach_details['username'],
            'password' => $raw_password,
            'team_name' => $coach_details['team_name'],
            'team_code' => $coach_details['team_code']
        ];
        $email_body = get_email_template('coach_welcome', $email_data);

        // Queue the email
        if (!queue_email($coach_details['email'], $email_subject, $email_body)) {
            throw new Exception('Failed to queue the credential email.');
        }

        $db->commit();

        // Log the activity
        if (isset($_SESSION['user_id'])) {
            log_activity($_SESSION['user_id'], 'manual_credentials_sent', "Manually sent new credentials to coach ID {$user_id}");
        }

        echo json_encode(['status' => 'success', 'message' => 'Credentials have been regenerated and the welcome email has been queued.']);

    } catch (Exception $e) {
        $db->rollBack();
        error_log('Manual credential sender error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch all approved coaches who might need credentials
$coaches = $db->fetchAll("
    SELECT 
        u.id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.username,
        t.name as team_name,
        cr.approved_at
    FROM users u
    JOIN coaches c ON u.id = c.user_id
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN coach_registrations cr ON u.id = cr.user_id
    WHERE u.role = 'coach' AND u.approval_status = 'approved'
    ORDER BY u.first_name, u.last_name
", []);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Credential Sender - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
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
                            <h2><i class="fas fa-paper-plane me-3"></i>Manual Credential Sender</h2>
                            <p class="text-muted">Regenerate and send login credentials to approved coaches.</p>
                        </div>
                    </div>

                    <div id="alert-container"></div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Approved Coaches (<?php echo count($coaches); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <p>This tool is for coaches who were approved but did not receive their login details. Clicking 'Send Credentials' will <strong>generate a new password</strong> and email it to them.</p>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Team</th>
                                            <th>Username</th>
                                            <th>Approved On</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($coaches)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No approved coaches found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($coaches as $coach): ?>
                                                <tr id="coach-row-<?php echo $coach['id']; ?>">
                                                    <td><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($coach['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($coach['team_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($coach['username'] ?? 'Not Set'); ?></td>
                                                    <td><?php echo $coach['approved_at'] ? date('M j, Y', strtotime($coach['approved_at'])) : 'N/A'; ?></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-primary btn-sm send-credentials-btn" data-id="<?php echo $coach['id']; ?>" data-name="<?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?>">
                                                            <i class="fas fa-key me-1"></i> Send Credentials
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const alertContainer = document.getElementById('alert-container');

        document.querySelectorAll('.send-credentials-btn').forEach(button => {
            button.addEventListener('click', function() {
                const coachId = this.dataset.id;
                const coachName = this.dataset.name;
                
                if (!confirm(`Are you sure you want to generate and send new credentials to ${coachName}? This will invalidate their old password.`)) {
                    return;
                }

                // Disable button to prevent multiple clicks
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

                const formData = new FormData();
                formData.append('action', 'send_credentials');
                formData.append('user_id', coachId);

                fetch('', { // POST to the same page
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('success', `Success! ${data.message}`);
                        const row = document.getElementById(`coach-row-${coachId}`);
                        row.classList.add('table-success');
                    } else {
                        showAlert('danger', `Error! ${data.message}`);
                        this.disabled = false; // Re-enable on error
                    }
                    this.innerHTML = '<i class="fas fa-key me-1"></i> Send Credentials';
                })
                .catch(error => {
                    showAlert('danger', 'A network error occurred. Please try again.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-key me-1"></i> Send Credentials';
                });
            });
        });

        function showAlert(type, message) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
        }
    });
    </script>
</body>
</html>
