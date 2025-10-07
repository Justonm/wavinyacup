<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user is logged in and has coach permissions
if (!is_logged_in() || !has_role('coach')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get coach information and team
$coach = $db->fetchRow("
    SELECT c.*, t.id as team_id, t.name as team_name, t.team_code, t.team_photo, w.name as ward_name 
    FROM coaches c 
    LEFT JOIN teams t ON c.team_id = t.id 
    LEFT JOIN wards w ON t.ward_id = w.id 
    WHERE c.user_id = ?
", [$user['id']]);

if (!$coach) {
    redirect('dashboard.php');
}

// Get players if team exists
$players = [];
$max_players = 15; // Default max players per team

if ($coach['team_id']) {
    $players = $db->fetchAll("
        SELECT * FROM players 
        WHERE team_id = ? AND is_active = 1 
        ORDER BY jersey_number ASC
    ", [$coach['team_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'View Team'; include dirname(__DIR__) . '/includes/head.php'; ?>
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
                            <h2><i class="fas fa-users me-2"></i>Team Overview</h2>
                            <p class="text-muted">View your team details and players (read-only)</p>
                        </div>
                        <div class="alert alert-info d-inline-block mb-0">
                            <i class="fas fa-info-circle me-2"></i>Contact admin for any changes
                        </div>
                    </div>

                    <?php if (!$coach['team_id']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You don't have a team assigned yet. Please contact an administrator.
                        </div>
                    <?php else: ?>
                        <!-- Team Information -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Team Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Team Name:</strong> <?php echo htmlspecialchars($coach['team_name']); ?></p>
                                                <p><strong>Team Code:</strong> <?php echo htmlspecialchars($coach['team_code']); ?></p>
                                                <p><strong>Ward:</strong> <?php echo htmlspecialchars($coach['ward_name']); ?></p>
                                                <p><strong>Players:</strong> <?php echo count($players); ?>/<?php echo $max_players; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <?php if ($coach['team_photo']): ?>
                                                    <img src="../<?php echo htmlspecialchars($coach['team_photo']); ?>" 
                                                         alt="Team Photo" class="img-fluid rounded" style="max-height: 200px;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                                        <i class="fas fa-users fa-3x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5><i class="fas fa-download me-2"></i>Export Options</h5>
                                        <a href="../viewer/export_team_pdf.php?id=<?php echo $coach['team_id']; ?>" 
                                           class="btn btn-primary w-100 mb-2" target="_blank">
                                            <i class="fas fa-file-pdf me-2"></i>Export Team PDF
                                        </a>
                                        <small class="text-muted">Download your team roster as PDF</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Players List -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Players (<?php echo count($players); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($players)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5>No Players Added</h5>
                                        <p class="text-muted">Contact an administrator to add players to your team.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Photo</th>
                                                    <th>Name</th>
                                                    <th>Jersey #</th>
                                                    <th>Position</th>
                                                    <th>Gender</th>
                                                    <th>Age</th>
                                                    <th>Contact</th>
                                                    <th>ID Number</th>
                                                    <th>Height/Weight</th>
                                                    <th>Preferred Foot</th>
                                                    <th>ID Photos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($players as $index => $player): ?>
                                                    <tr>
                                                        <td><strong><?php echo $index + 1; ?></strong></td>
                                                        <td>
                                                            <?php if ($player['player_image']): ?>
                                                                <a href="#" class="image-modal-trigger" data-bs-toggle="modal" 
                                                                   data-bs-target="#imageModal" 
                                                                   data-img-src="../<?php echo htmlspecialchars($player['player_image']); ?>" 
                                                                   data-img-title="Player Photo: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                    <img src="../<?php echo htmlspecialchars($player['player_image']); ?>?v=<?php echo time(); ?>" 
                                                                         alt="Player Photo" style="width: 60px; height: 60px; object-fit: cover; border-radius: 0.25rem;">
                                                                </a>
                                                            <?php else: ?>
                                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                                     style="width: 60px; height: 60px;">
                                                                    <i class="fas fa-user text-white"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><strong><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></strong></td>
                                                        <td><span class="badge bg-secondary">#<?php echo htmlspecialchars($player['jersey_number']); ?></span></td>
                                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($player['position']); ?></span></td>
                                                        <td><?php echo ucfirst(htmlspecialchars($player['gender'] ?? 'N/A')); ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($player['date_of_birth']) {
                                                                $age = date_diff(date_create($player['date_of_birth']), date_create('today'))->y;
                                                                echo $age . ' years';
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                <?php if ($player['email']): ?>
                                                                    <i class="fas fa-envelope me-1"></i>
                                                                    <a href="mailto:<?php echo htmlspecialchars($player['email']); ?>">
                                                                        <?php echo htmlspecialchars($player['email']); ?>
                                                                    </a><br>
                                                                <?php endif; ?>
                                                                <?php if ($player['phone']): ?>
                                                                    <i class="fas fa-phone me-1"></i>
                                                                    <a href="tel:<?php echo htmlspecialchars($player['phone']); ?>">
                                                                        <?php echo htmlspecialchars($player['phone']); ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($player['id_number']); ?></td>
                                                        <td>
                                                            <small>
                                                                <?php if ($player['height_cm']): ?>
                                                                    <?php echo htmlspecialchars($player['height_cm']); ?>cm<br>
                                                                <?php endif; ?>
                                                                <?php if ($player['weight_kg']): ?>
                                                                    <?php echo htmlspecialchars($player['weight_kg']); ?>kg
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td><?php echo ucfirst(htmlspecialchars($player['preferred_foot'] ?? 'N/A')); ?></td>
                                                        <td>
                                                            <?php if ($player['id_photo_front'] || $player['id_photo_back']): ?>
                                                                <div class="d-flex gap-1">
                                                                    <?php if ($player['id_photo_front']): ?>
                                                                        <a href="#" class="image-modal-trigger" data-bs-toggle="modal" 
                                                                           data-bs-target="#imageModal" 
                                                                           data-img-src="../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" 
                                                                           data-img-title="ID Front: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                            <img src="../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" 
                                                                                 alt="ID Front" style="width: 40px; height: 25px; object-fit: cover; border-radius: 0.25rem;">
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <?php if ($player['id_photo_back']): ?>
                                                                        <a href="#" class="image-modal-trigger" data-bs-toggle="modal" 
                                                                           data-bs-target="#imageModal" 
                                                                           data-img-src="../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" 
                                                                           data-img-title="ID Back: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                            <img src="../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" 
                                                                                 alt="ID Back" style="width: 40px; height: 25px; object-fit: cover; border-radius: 0.25rem;">
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <small class="text-muted">No ID photos</small>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle image modal
            var imageModal = document.getElementById('imageModal');
            imageModal.addEventListener('show.bs.modal', function (event) {
                var triggerElement = event.relatedTarget;
                var imageSrc = triggerElement.getAttribute('data-img-src');
                var imageTitle = triggerElement.getAttribute('data-img-title');
                
                var modalImage = imageModal.querySelector('#modalImage');
                var modalTitle = imageModal.querySelector('#imageModalLabel');
                
                modalImage.src = imageSrc;
                modalTitle.textContent = imageTitle;
            });
        });
    </script>
</body>
</html>
