<?php
// admin/edit_player.php - Admin edit player (all fields)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/image_upload.php';

if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/admin_login.php');
}

$db = db();
$player_id = (int)($_GET['id'] ?? 0);
if ($player_id <= 0) {
    redirect('players.php');
}

// Load player with joined user data
$player = $db->fetchRow(
    "SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.id as user_id
     FROM players p
     JOIN users u ON u.id = p.user_id
     WHERE p.id = ?",
    [$player_id]
);

if (!$player) {
    redirect('players.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $id_number  = trim($_POST['id_number'] ?? '');

    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $position = $_POST['position'] ?? '';
    $jersey_number = (int)($_POST['jersey_number'] ?? 0);
    $height_cm = (int)($_POST['height_cm'] ?? 0);
    $weight_kg = (float)($_POST['weight_kg'] ?? 0);
    $preferred_foot = $_POST['preferred_foot'] ?? 'right';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (!$first_name || !$last_name || !$id_number) {
        $error = 'First name, last name and ID number are required.';
    }
    if (!$error && $email && !validate_email($email)) {
        $error = 'Please enter a valid email address.';
    }

    // Unique checks (exclude current user)
    if (!$error && $email) {
        $dup = $db->fetchRow('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $player['user_id']]);
        if ($dup) $error = 'Another user with this email exists.';
    }
    if (!$error && $id_number) {
        $dup = $db->fetchRow('SELECT id FROM users WHERE id_number = ? AND id != ?', [$id_number, $player['user_id']]);
        if ($dup) $error = 'Another user with this ID number exists.';
    }

    $uploaded_files = [];
    $old_files = [];

    if (!$error) {
        try {
            $db->beginTransaction();

            // Handle images
            $player_image = $player['player_image'];
            if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] === UPLOAD_ERR_OK) {
                $res = upload_image($_FILES['player_image'], 'player', 'photo');
                if (!$res['success']) { $res = upload_image_simple($_FILES['player_image'], 'player', 'photo'); }
                if (!$res['success']) throw new Exception('Player photo: ' . $res['error']);
                if (!empty($player_image)) $old_files[] = $player_image;
                $player_image = $res['path'];
                $uploaded_files[] = $player_image;
            }

            $id_front = $player['id_photo_front'];
            if (isset($_FILES['id_photo_front']) && $_FILES['id_photo_front']['error'] === UPLOAD_ERR_OK) {
                $res = upload_image($_FILES['id_photo_front'], 'id', 'f');
                if (!$res['success']) { $res = upload_image_simple($_FILES['id_photo_front'], 'id', 'f'); }
                if (!$res['success']) throw new Exception('ID Front: ' . $res['error']);
                if (!empty($id_front)) $old_files[] = $id_front;
                $id_front = $res['path'];
                $uploaded_files[] = $id_front;
            }

            $id_back = $player['id_photo_back'];
            if (isset($_FILES['id_photo_back']) && $_FILES['id_photo_back']['error'] === UPLOAD_ERR_OK) {
                $res = upload_image($_FILES['id_photo_back'], 'id', 'b');
                if (!$res['success']) { $res = upload_image_simple($_FILES['id_photo_back'], 'id', 'b'); }
                if (!$res['success']) throw new Exception('ID Back: ' . $res['error']);
                if (!empty($id_back)) $old_files[] = $id_back;
                $id_back = $res['path'];
                $uploaded_files[] = $id_back;
            }

            // Update users
            $db->query(
                'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, updated_at = NOW() WHERE id = ?',
                [$first_name, $last_name, $email, $phone, $id_number, $player['user_id']]
            );

            // Update players
            $db->query(
                'UPDATE players SET gender = ?, position = ?, jersey_number = ?, height_cm = ?, weight_kg = ?, date_of_birth = ?, player_image = ?, preferred_foot = ?, id_photo_front = ?, id_photo_back = ?, is_active = ? WHERE id = ?',
                [$gender, $position, $jersey_number, $height_cm, $weight_kg, $date_of_birth, $player_image, $preferred_foot, $id_front, $id_back, $is_active, $player_id]
            );

            $db->commit();
            foreach ($old_files as $f) { delete_image($f); }
            $success = 'Player updated successfully.';

            // Refresh data
            $player = $db->fetchRow(
                "SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number, u.id as user_id
                 FROM players p JOIN users u ON u.id = p.user_id WHERE p.id = ?",
                [$player_id]
            );
        } catch (Exception $e) {
            $db->rollBack();
            foreach ($uploaded_files as $f) { delete_image($f); }
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="fas fa-user-edit me-2"></i>Edit Player</h3>
        <a href="upload_photos.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name *</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($player['first_name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name *</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($player['last_name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($player['email']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($player['phone']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">ID Number *</label>
                <input type="text" name="id_number" class="form-control" value="<?php echo htmlspecialchars($player['id_number']); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="" <?php echo $player['gender']==='' ? 'selected' : '' ?>>Select</option>
                    <option value="male" <?php echo $player['gender']==='male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?php echo $player['gender']==='female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Preferred Foot</label>
                <select name="preferred_foot" class="form-select">
                    <option value="right" <?php echo $player['preferred_foot']==='right' ? 'selected' : '' ?>>Right</option>
                    <option value="left" <?php echo $player['preferred_foot']==='left' ? 'selected' : '' ?>>Left</option>
                    <option value="both" <?php echo $player['preferred_foot']==='both' ? 'selected' : '' ?>>Both</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jersey #</label>
                <input type="number" name="jersey_number" class="form-control" value="<?php echo htmlspecialchars($player['jersey_number']); ?>" min="1" max="99">
            </div>
            <div class="col-md-3">
                <label class="form-label">Position</label>
                <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($player['position']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($player['date_of_birth']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Height (cm)</label>
                <input type="number" name="height_cm" class="form-control" value="<?php echo htmlspecialchars($player['height_cm']); ?>" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Weight (kg)</label>
                <input type="number" name="weight_kg" class="form-control" value="<?php echo htmlspecialchars($player['weight_kg']); ?>" step="0.1" min="0">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php echo $player['is_active'] ? 'checked' : '';?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <hr class="mt-3">
            <div class="col-md-4">
                <label class="form-label">Player Photo</label>
                <?php if ($player['player_image']): ?>
                    <div class="mb-2"><img src="../<?php echo htmlspecialchars($player['player_image']); ?>?v=<?php echo time(); ?>" class="img-fluid rounded" style="max-height:180px"></div>
                <?php endif; ?>
                <input type="file" name="player_image" class="form-control" accept="image/*">
            </div>
            <div class="col-md-4">
                <label class="form-label">ID Front</label>
                <?php if ($player['id_photo_front']): ?>
                    <div class="mb-2"><img src="../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" class="img-fluid rounded" style="max-height:180px"></div>
                <?php endif; ?>
                <input type="file" name="id_photo_front" class="form-control" accept="image/*">
            </div>
            <div class="col-md-4">
                <label class="form-label">ID Back</label>
                <?php if ($player['id_photo_back']): ?>
                    <div class="mb-2"><img src="../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" class="img-fluid rounded" style="max-height:180px"></div>
                <?php endif; ?>
                <input type="file" name="id_photo_back" class="form-control" accept="image/*">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
