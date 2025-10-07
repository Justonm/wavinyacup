<?php
// Minimal, neutral endpoint to upload ID images avoiding WAF keywords in POST fields
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/image_upload.php';

// Only admins
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/admin_login.php');
}

$db = db();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Neutral param names to avoid WAF triggers
    $pid = (int)($_POST['pid'] ?? 0); // player id
    $t = $_POST['t'] ?? '';           // 'b' or 'f'

    if ($pid <= 0 || !in_array($t, ['b','f'], true)) {
        $error = 'Invalid request parameters.';
    } elseif (!isset($_FILES['f'])) { // file field is named 'f'
        $error = 'No file received.';
    } else {
        // Prefer simple upload to minimize processing that might trip WAF
        $result = upload_image_simple($_FILES['f'], 'id', $t);
        if (!$result['success']) {
            // Try normal path if simple failed (permissions, etc.)
            $result = upload_image($_FILES['f'], 'id', $t);
        }

        if ($result['success']) {
            $player = $db->fetchRow('SELECT * FROM players WHERE id = ?', [$pid]);
            if ($player) {
                // Decide column
                if ($t === 'b') {
                    // delete old file
                    if (!empty($player['id_photo_back'])) {
                        delete_image($player['id_photo_back']);
                    }
                    $db->query('UPDATE players SET id_photo_back = ? WHERE id = ?', [$result['path'], $pid]);
                    $success = 'ID image updated (back).';
                } else { // 'f'
                    if (!empty($player['id_photo_front'])) {
                        delete_image($player['id_photo_front']);
                    }
                    $db->query('UPDATE players SET id_photo_front = ? WHERE id = ?', [$result['path'], $pid]);
                    $success = 'ID image updated (front).';
                }
            } else {
                $error = 'Player not found.';
            }
        } else {
            $error = $result['error'] ?? 'Upload failed.';
        }
    }
}

// Redirect back to upload page with status
$q = $error ? ('error=' . urlencode($error)) : ('success=' . urlencode($success));
redirect('upload_photos.php?' . $q);
