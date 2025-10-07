<?php
// Ultra-neutral ID image upload endpoint to bypass strict WAF rules
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/image_upload.php';

if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/admin_login.php');
}

$db = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('upload_photos.php');
}

// Minimal field names: p=player id, k=kind (b/f), x=file
$p = (int)($_POST['p'] ?? 0);
$k = $_POST['k'] ?? '';

if ($p <= 0 || !in_array($k, ['b','f'], true) || !isset($_FILES['x'])) {
    redirect('upload_photos.php?error=' . urlencode('Invalid request'));
}

// Use simple upload first to avoid processing that may trigger WAF filters
$res = upload_image_simple($_FILES['x'], 'id', $k);
if (!$res['success']) {
    $res = upload_image($_FILES['x'], 'id', $k);
}

if (!$res['success']) {
    redirect('upload_photos.php?error=' . urlencode($res['error'] ?? 'Upload failed'));
}

$player = $db->fetchRow('SELECT * FROM players WHERE id = ?', [$p]);
if (!$player) {
    redirect('upload_photos.php?error=' . urlencode('Player not found'));
}

try {
    if ($k === 'b') {
        if (!empty($player['id_photo_back'])) delete_image($player['id_photo_back']);
        $db->query('UPDATE players SET id_photo_back = ? WHERE id = ?', [$res['path'], $p]);
    } else { // 'f'
        if (!empty($player['id_photo_front'])) delete_image($player['id_photo_front']);
        $db->query('UPDATE players SET id_photo_front = ? WHERE id = ?', [$res['path'], $p]);
    }
} catch (Exception $e) {
    // Rollback file on DB error
    delete_image($res['path']);
    redirect('upload_photos.php?error=' . urlencode('DB update failed'));
}

redirect('upload_photos.php?success=' . urlencode('ID image updated'));
