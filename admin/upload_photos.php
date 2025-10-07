<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/image_upload.php';

// Check if user is logged in and has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/admin_login.php');
}

$user = get_logged_in_user();
$db = db();

$success = '';
$error = '';

// Handle photo uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_type = $_POST['upload_type'] ?? '';
    $entity_id = (int)($_POST['entity_id'] ?? 0);
    
    if ($upload_type === 'player_photo' && isset($_FILES['photo'])) {
        $result = upload_image($_FILES['photo'], 'player');
        if (!$result['success']) {
            // Fallback to simple upload (no resize) to avoid server restrictions
            $result = upload_image_simple($_FILES['photo'], 'player');
        }
        if ($result['success']) {
            // Get current player data
            $player = $db->fetchRow("SELECT * FROM players WHERE id = ?", [$entity_id]);
            if ($player) {
                // Delete old image if exists
                if ($player['player_image']) {
                    delete_image($player['player_image']);
                }
                
                // Update player record - use the full path from upload_image
                $db->query("UPDATE players SET player_image = ? WHERE id = ?", 
                    [$result['path'], $entity_id]);
                
                $success = 'Player photo uploaded successfully!';
            } else {
                $error = 'Player not found.';
            }
        } else {
            $error = $result['error'];
        }
    }
    
    if ($upload_type === 'player_id_front' && isset($_FILES['photo'])) {
        $result = upload_image($_FILES['photo'], 'id', 'f');
        if (!$result['success']) {
            // Fallback to simple upload (no resize)
            $result = upload_image_simple($_FILES['photo'], 'id', 'f');
        }
        if ($result['success']) {
            // Get current player data
            $player = $db->fetchRow("SELECT * FROM players WHERE id = ?", [$entity_id]);
            if ($player) {
                // Delete old image if exists
                if ($player['id_photo_front']) {
                    delete_image($player['id_photo_front']);
                }
                
                // Update player record
                $db->query("UPDATE players SET id_photo_front = ? WHERE id = ?", 
                    [$result['path'], $entity_id]);
                
                $success = 'Player ID front photo uploaded successfully!';
            } else {
                $error = 'Player not found.';
            }
        } else {
            $error = $result['error'];
        }
    }
    
    if (in_array($upload_type, ['player_id_back', 'player_id_b']) && isset($_FILES['photo'])) {
        $result = upload_image($_FILES['photo'], 'id', 'b');
        if (!$result['success']) {
            // Fallback to simple upload (no resize)
            $result = upload_image_simple($_FILES['photo'], 'id', 'b');
        }
        if ($result['success']) {
            // Get current player data
            $player = $db->fetchRow("SELECT * FROM players WHERE id = ?", [$entity_id]);
            if ($player) {
                // Delete old image if exists
                if ($player['id_photo_back']) {
                    delete_image($player['id_photo_back']);
                }
                
                // Update player record
                $db->query("UPDATE players SET id_photo_back = ? WHERE id = ?", 
                    [$result['path'], $entity_id]);
                
                $success = 'Player ID back photo uploaded successfully!';
            } else {
                $error = 'Player not found.';
            }
        } else {
            $error = $result['error'];
        }
    }
    
    if ($upload_type === 'team_photo' && isset($_FILES['photo'])) {
        $result = upload_image($_FILES['photo'], 'teams');
        if (!$result['success']) {
            $result = upload_image_simple($_FILES['photo'], 'teams');
        }
        if ($result['success']) {
            $team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$entity_id]);
            if ($team) {
                if ($team['team_photo']) {
                    delete_image($team['team_photo']);
                }
                
                $db->query("UPDATE teams SET team_photo = ? WHERE id = ?", 
                    [$result['path'], $entity_id]);
                
                $success = 'Team photo uploaded successfully!';
            } else {
                $error = 'Team not found.';
            }
        } else {
            $error = $result['error'];
        }
    }
    
    if ($upload_type === 'coach_photo' && isset($_FILES['photo'])) {
        $result = upload_image($_FILES['photo'], 'coaches');
        if (!$result['success']) {
            $result = upload_image_simple($_FILES['photo'], 'coaches');
        }
        if ($result['success']) {
            $coach = $db->fetchRow("SELECT * FROM coaches WHERE id = ?", [$entity_id]);
            if ($coach) {
                if ($coach['coach_image']) {
                    delete_image($coach['coach_image']);
                }
                
                $db->query("UPDATE coaches SET coach_image = ? WHERE id = ?", 
                    [$result['path'], $entity_id]);
                
                $success = 'Coach photo uploaded successfully!';
            } else {
                $error = 'Coach not found.';
            }
        } else {
            $error = $result['error'];
        }
    }
}

// Get entities missing photos
$players_missing_photos = $db->fetchAll("
    SELECT p.id, u.first_name, u.last_name, p.player_image, p.id_photo_front, p.id_photo_back,
           t.name as team_name, t.team_code
    FROM players p 
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN teams t ON p.team_id = t.id 
    WHERE p.is_active = 1 
    AND (p.player_image IS NULL OR p.player_image = '' 
         OR p.id_photo_front IS NULL OR p.id_photo_front = '' 
         OR p.id_photo_back IS NULL OR p.id_photo_back = '')
    ORDER BY t.name, u.first_name, u.last_name
");

$teams_missing_photos = $db->fetchAll("
    SELECT t.id, t.name, t.team_code, t.team_photo, w.name as ward_name
    FROM teams t 
    LEFT JOIN wards w ON t.ward_id = w.id 
    WHERE t.team_photo IS NULL OR t.team_photo = ''
    ORDER BY t.name
");

$coaches_missing_photos = $db->fetchAll("
    SELECT c.id, u.first_name, u.last_name, c.coach_image, t.name as team_name
    FROM coaches c 
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN teams t ON t.coach_id = u.id 
    WHERE c.coach_image IS NULL OR c.coach_image = ''
    ORDER BY u.first_name, u.last_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Photos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                            <h2><i class="fas fa-camera me-2"></i>Upload Missing Photos</h2>
                            <p class="text-muted">Upload photos for players, teams, and coaches who are missing them.</p>
                        </div>
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

                    <!-- Players Missing Photos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Players Missing Photos (<?php echo count($players_missing_photos); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($players_missing_photos)): ?>
                                <p class="text-success"><i class="fas fa-check me-2"></i>All players have complete photos!</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Player</th>
                                                <th>Team</th>
                                                <th>Missing Photos</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($players_missing_photos as $player): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($player['team_name'] ?? 'No Team'); ?>
                                                        <?php if ($player['team_code']): ?>
                                                            <small class="text-muted">(<?php echo htmlspecialchars($player['team_code']); ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($player['player_image'])): ?>
                                                            <span class="badge bg-warning me-1">Player Photo</span>
                                                        <?php endif; ?>
                                                        <?php if (empty($player['id_photo_front'])): ?>
                                                            <span class="badge bg-warning me-1">ID Front</span>
                                                        <?php endif; ?>
                                                        <?php if (empty($player['id_photo_back'])): ?>
                                                            <span class="badge bg-warning me-1">ID Back</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($player['player_image'])): ?>
                                                            <button class="btn btn-sm btn-primary me-1 mb-1" 
                                                                    onclick="openUploadModal('player_photo', <?php echo $player['id']; ?>, 'Player Photo for <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>')">
                                                                <i class="fas fa-user"></i> Photo
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (empty($player['id_photo_front'])): ?>
                                                            <button class="btn btn-sm btn-secondary me-1 mb-1" 
                                                                    onclick="openUploadModal('player_id_front', <?php echo $player['id']; ?>, 'ID Front for <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>')">
                                                                <i class="fas fa-id-card"></i> ID Front
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (empty($player['id_photo_back'])): ?>
                                                            <button class="btn btn-sm btn-secondary me-1 mb-1" 
                                                                    onclick="openUploadModal('player_id_b', <?php echo $player['id']; ?>, 'ID Back for <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>')">
                                                                <i class="fas fa-id-card"></i> ID Back
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Teams Missing Photos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Teams Missing Photos (<?php echo count($teams_missing_photos); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teams_missing_photos)): ?>
                                <p class="text-success"><i class="fas fa-check me-2"></i>All teams have photos!</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Team</th>
                                                <th>Ward</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teams_missing_photos as $team): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                                        <small class="text-muted d-block"><?php echo htmlspecialchars($team['team_code']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($team['ward_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="openUploadModal('team_photo', <?php echo $team['id']; ?>, 'Team Photo for <?php echo htmlspecialchars($team['name']); ?>')">
                                                            <i class="fas fa-camera"></i> Upload Photo
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Coaches Missing Photos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Coaches Missing Photos (<?php echo count($coaches_missing_photos); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($coaches_missing_photos)): ?>
                                <p class="text-success"><i class="fas fa-check me-2"></i>All coaches have photos!</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Coach</th>
                                                <th>Team</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($coaches_missing_photos as $coach): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($coach['team_name'] ?? 'No Team Assigned'); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="openUploadModal('coach_photo', <?php echo $coach['id']; ?>, 'Coach Photo for <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?>')">
                                                            <i class="fas fa-camera"></i> Upload Photo
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="upload_type" id="upload_type">
                        <input type="hidden" name="entity_id" id="entity_id">
                        <!-- Ultra-neutral endpoint fields for ID uploads -->
                        <input type="hidden" name="p" id="p">
                        <input type="hidden" name="k" id="k">
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Select Photo</label>
                            <input type="file" class="form-control" name="photo" id="photo" accept="image/*" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Supported formats: JPG, PNG, GIF. Maximum size: 5MB.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i>Upload Photo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openUploadModal(uploadType, entityId, title) {
            const form = document.getElementById('uploadForm');
            const fileInput = document.getElementById('photo');
            const p = document.getElementById('p');
            const k = document.getElementById('k');

            // Default action: post back to this same page
            form.action = '';
            // Default file field name expected by this page
            fileInput.setAttribute('name', 'photo');
            // Clear neutral fields by default
            p.value = '';
            k.value = '';

            document.getElementById('upload_type').value = uploadType;
            document.getElementById('entity_id').value = entityId;
            document.getElementById('uploadModalLabel').textContent = title;

            // Route ID Front/Back to ultra-neutral endpoint using ultra-neutral field names
            if (uploadType === 'player_id_front') {
                form.action = 'u.php';
                fileInput.setAttribute('name', 'x'); // file field expected by ultra-neutral endpoint
                p.value = String(entityId);
                k.value = 'f';
            } else if (uploadType === 'player_id_b' || uploadType === 'player_id_back') {
                form.action = 'u.php';
                fileInput.setAttribute('name', 'x');
                p.value = String(entityId);
                k.value = 'b';
            }

            var modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            modal.show();
        }
    </script>
</body>
</html>
