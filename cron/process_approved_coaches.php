<?php
// cron/process_approved_coaches.php - Finalizes coach approvals in the background.
// This script should be run by a cron job (e.g., every minute).

set_time_limit(300); // 5 minutes

$lock_file = __DIR__ . '/process_approvals.lock';
$lock_handle = fopen($lock_file, 'w');

if (!$lock_handle || !flock($lock_handle, LOCK_EX | LOCK_NB)) {
    echo "Approval processing cron job is already running. Exiting.\n";
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

date_default_timezone_set('UTC');
echo "Starting approved coach processing at " . date('Y-m-d H:i:s') . "...\n";

$db = db();

// Fetch registrations marked for processing
$registrations_to_process = $db->fetchAll("SELECT * FROM coach_registrations WHERE status = 'processing' ORDER BY created_at ASC LIMIT 10");

if (empty($registrations_to_process)) {
    echo "No registrations to process. Exiting.\n";
} else {
    echo "Found " . count($registrations_to_process) . " registrations to process.\n";
}

foreach ($registrations_to_process as $reg) {
    $registration_id = $reg['id'];
    echo "Processing registration ID: {$registration_id}... ";

    try {
        $db->beginTransaction();

        // Fetch all details needed for the approval
        $registration = $db->fetchRow("
            SELECT cr.*, u.first_name, u.last_name, u.email, u.username, u.temp_password, 
                   c.license_number, c.license_type, c.experience_years, c.specialization
            FROM coach_registrations cr
            JOIN users u ON cr.user_id = u.id
            JOIN coaches c ON c.user_id = u.id
            WHERE cr.id = ? FOR UPDATE
        ", [$registration_id]);

        if (!$registration || $registration['status'] !== 'processing') {
            echo "Skipped (not in 'processing' state).\n";
            $db->rollBack(); // Release the lock
            continue;
        }

        // Get ward details to generate team code
        $ward = $db->fetchRow("SELECT * FROM wards WHERE id = ?", [$registration['ward_id']]);
        $team_code = generate_team_code($ward['code']);

        if (!$team_code) {
            throw new Exception('Failed to generate a unique team code.');
        }

        // Create team
        $db->query("
            INSERT INTO teams (name, ward_id, coach_id, team_code, status) 
            VALUES (?, ?, ?, ?, 'active')
        ", [$registration['team_name'], $registration['ward_id'], $registration['user_id'], $team_code]);
        $team_id = $db->lastInsertId();

        // Update coach with team assignment
        $db->query("UPDATE coaches SET team_id = ? WHERE user_id = ?", [$team_id, $registration['user_id']]);

        // Update user approval status
        $db->query("UPDATE users SET approval_status = 'approved', approved_at = NOW() WHERE id = ?", [$registration['user_id']]);

        // Update registration status to 'approved'
        $db->query("UPDATE coach_registrations SET status = 'approved', approved_at = NOW() WHERE id = ?", [$registration_id]);

        $db->commit();

        // Queue the approval email
        $email_subject = "🎉 Coach Registration Approved - Welcome to " . APP_NAME;
        $login_url = BASE_URL . 'auth/coach_login.php';
        $email_body = get_email_template('coach_approval', [
            'first_name' => $registration['first_name'],
            'username' => $registration['username'], // Assuming username is available
            'temp_password' => $registration['temp_password'],
            'login_url' => $login_url,
            'team_code' => $team_code
        ]);
        
        if (queue_email($registration['email'], $email_subject, $email_body)) {
            echo "Success. Email queued.\n";
        } else {
            echo "Success, but email queueing failed.\n";
            error_log("Failed to queue approval email for registration ID {$registration_id}");
        }

    } catch (Exception $e) {
        $db->rollBack();
        // Mark as failed to prevent retrying indefinitely
        $db->query("UPDATE coach_registrations SET status = 'failed' WHERE id = ?", [$registration_id]);
        error_log("Failed to process registration ID {$registration_id}: " . $e->getMessage());
        echo "Failed. See error log.\n";
    }
}

// Release the lock
flock($lock_handle, LOCK_UN);
fclose($lock_handle);

echo "Coach processing finished.\n";
exit;
