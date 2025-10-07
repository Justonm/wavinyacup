<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/image_upload.php';

// Check if user is logged in and has admin permissions (coaches cannot upload images)
if (!is_logged_in() || !has_role('admin')) {
    if (has_role('coach')) {
        // Redirect coaches with a specific message
        $_SESSION['error'] = 'Coaches do not have permission to upload images. Please contact an administrator.';
        redirect('view_team.php');
    } else {
        redirect('../auth/login.php');
    }
}

$user = get_logged_in_user();
$db = db();

// Get coach information and team
$coach = $db->fetchRow("
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, w.name as ward_name 
    FROM coaches c 
    LEFT JOIN teams t ON c.team_id = t.id 
    LEFT JOIN wards w ON t.ward_id = w.id 
    WHERE c.user_id = ?
", [$user['id']]);

if (!$coach || !$coach['team_id']) {
    redirect('dashboard.php');
}

// Get player ID from URL
$player_id = isset($_GET['player_id']) ? (int)$_GET['player_id'] : 0;

// Verify player belongs to coach's team
$player = $db->fetchRow("
    SELECT * FROM players 
    WHERE id = ? AND team_id = ? AND is_active = 1
", [$player_id, $coach['team_id']]);

if (!$player) {
    redirect('manage_team.php');
}

$success = '';
$error = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_type = $_POST['upload_type'] ?? '';
    
    if ($upload_type === 'player_photo' && isset($_FILES['player_image'])) {
        $result = upload_image($_FILES['player_image'], 'player');
        if ($result['success']) {
            // Delete old image if exists
            if ($player['player_image']) {
                delete_image($player['player_image']);
            }
            
            // Update player record
            $db->execute("UPDATE players SET player_image = ? WHERE id = ?", 
                [$result['filename'], $player_id]);
            
            $success = 'Player photo uploaded successfully!';
            $player['player_image'] = $result['filename'];
        } else {
            $error = $result['error'];
        }
    }
    
    if ($upload_type === 'id_front' && isset($_FILES['id_photo_front'])) {
        $result = upload_image($_FILES['id_photo_front'], 'id');
        if ($result['success']) {
            // Delete old image if exists
            if ($player['id_photo_front']) {
                delete_image($player['id_photo_front']);
            }
            
            // Update player record
            $db->execute("UPDATE players SET id_photo_front = ? WHERE id = ?", 
                [$result['filename'], $player_id]);
            
            $success = 'ID front photo uploaded successfully!';
            $player['id_photo_front'] = $result['filename'];
        } else {
            $error = $result['error'];
        }
    }
    
    if ($upload_type === 'id_back' && isset($_FILES['id_photo_back'])) {
        $result = upload_image($_FILES['id_photo_back'], 'id');
        if ($result['success']) {
            // Delete old image if exists
            if ($player['id_photo_back']) {
                delete_image($player['id_photo_back']);
            }
            
            // Update player record
            $db->execute("UPDATE players SET id_photo_back = ? WHERE id = ?", 
                [$result['filename'], $player_id]);
            
            $success = 'ID back photo uploaded successfully!';
            $player['id_photo_back'] = $result['filename'];
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Upload Player Images'; include dirname(__DIR__) . '/includes/head.php'; ?>
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
                            <h2><i class="fas fa-camera me-2"></i>Upload Player Images</h2>
                            <p class="text-muted">Upload photos for <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></p>
                        </div>
                        <a href="manage_team.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Team
                        </a>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Player Photo Upload -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Player Photo</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($player['player_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($player['player_image']); ?>?v=<?php echo time(); ?>" 
                                             alt="Player Photo" class="img-fluid mb-3" style="max-height: 200px;">
                                        <p class="text-success"><i class="fas fa-check me-1"></i>Photo uploaded</p>
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 200px;">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                        <p class="text-muted">No photo uploaded</p>
                                    <?php endif; ?>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="upload_type" value="player_photo">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="player_image" accept="image/*" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-1"></i>Upload Photo
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- ID Front Photo Upload -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>ID Front Photo</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($player['id_photo_front']): ?>
                                        <img src="../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" 
                                             alt="ID Front" class="img-fluid mb-3" style="max-height: 200px;">
                                        <p class="text-success"><i class="fas fa-check me-1"></i>Photo uploaded</p>
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 200px;">
                                            <i class="fas fa-id-card fa-3x text-muted"></i>
                                        </div>
                                        <p class="text-muted">No ID front photo</p>
                                    <?php endif; ?>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="upload_type" value="id_front">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="id_photo_front" accept="image/*" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-1"></i>Upload ID Front
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- ID Back Photo Upload -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>ID Back Photo</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($player['id_photo_back']): ?>
                                        <img src="../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" 
                                             alt="ID Back" class="img-fluid mb-3" style="max-height: 200px;">
                                        <p class="text-success"><i class="fas fa-check me-1"></i>Photo uploaded</p>
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 200px;">
                                            <i class="fas fa-id-card fa-3x text-muted"></i>
                                        </div>
                                        <p class="text-muted">No ID back photo</p>
                                    <?php endif; ?>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="upload_type" value="id_back">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="id_photo_back" accept="image/*" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-1"></i>Upload ID Back
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5><i class="fas fa-info-circle me-2"></i>Upload Guidelines</h5>
                            <ul class="mb-0">
                                <li>Upload images one at a time to avoid server restrictions</li>
                                <li>Supported formats: JPG, PNG, GIF</li>
                                <li>Maximum file size: 5MB per image</li>
                                <li>For best results, use clear, well-lit photos</li>
                                <li>ID photos should be readable and show all details clearly</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
