<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_logged_in() || !has_role('admin')) {
    redirect('../../auth/login.php');
}

$db = db();
$team_id = $_GET['id'] ?? null;

if (!$team_id) {
    redirect('../teams.php');
}

// Fetch team details with ward, sub-county, and coach
$team = $db->fetchRow("
    SELECT 
        t.*, 
        w.name as ward_name, 
        sc.name as sub_county_name,
        CONCAT(u.first_name, ' ', u.last_name) as coach_name
    FROM teams t
    LEFT JOIN wards w ON t.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    LEFT JOIN coaches c ON t.id = c.team_id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE t.id = ?
", [$team_id]);

if (!$team) {
    $_SESSION['error_message'] = 'Team not found.';
    redirect('../teams.php');
}

// Fetch players with all details
$players = $db->fetchAll("
    SELECT 
        p.*, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.phone, 
        u.id_number
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1
    ORDER BY p.jersey_number ASC
", [$team_id]);

$page_title = 'View Team: ' . htmlspecialchars($team['name']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
    <style>
        .table thead th {
            background-color: #007bff !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0">
                <?php include __DIR__ . '/../sidebar.php'; ?>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-shield-alt me-2"></i><?php echo htmlspecialchars($team['name']); ?></h2>
                            <p class="text-muted mb-0">Team Details</p>
                        </div>
                        <div>
                            <a href="../teams.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Teams</a>
                            <a href="export_team_pdf.php?id=<?php echo $team_id; ?>" class="btn btn-primary"><i class="fas fa-file-pdf me-2"></i>Export PDF</a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-image me-2"></i>Team Photo</h5>
                                    <?php if ($team['team_photo']): ?>
                                        <img src="../../<?php echo htmlspecialchars($team['team_photo']); ?>" alt="<?php echo htmlspecialchars($team['name']); ?>" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="text-muted py-4"><i class="fas fa-image fa-3x mb-2"></i><p>No photo</p></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5><i class="fas fa-info-circle me-2"></i>Team Information</h5>
                                    <p><strong>Team Name:</strong> <?php echo htmlspecialchars($team['name']); ?></p>
                                    <p><strong>Team Code:</strong> <?php echo htmlspecialchars($team['team_code']); ?></p>
                                    <p><strong>Coach:</strong> <?php echo htmlspecialchars($team['coach_name'] ?? 'N/A'); ?></p>
                                    <p><strong>Ward:</strong> <?php echo htmlspecialchars($team['ward_name']); ?></p>
                                    <p><strong>Sub-County:</strong> <?php echo htmlspecialchars($team['sub_county_name']); ?></p>
                                    <p><strong>Players:</strong> <span class="badge bg-info"><?php echo count($players); ?> / 22</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Players (<?php echo count($players); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Photo</th>
                                            <th>Name</th>
                                            <th>Jersey #</th>
                                            <th>Position</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Height/Weight</th>
                                            <th>Preferred Foot</th>
                                            <th>Contact</th>
                                            <th>ID Number</th>
                                            <th>ID Photos</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($players as $index => $player): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $index + 1; ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($player['player_image']): ?>
                                                        <a href="#" class="image-modal-trigger" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="../../<?php echo htmlspecialchars($player['player_image']); ?>" data-img-title="Player Photo: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                            <img src="../../<?php echo htmlspecialchars($player['player_image']); ?>?v=<?php echo time(); ?>" alt="Player" style="width: 50px; height: 50px; object-fit: cover;" class="rounded-circle">
                                                        </a>
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></strong></td>
                                                <td><span class="badge bg-secondary">#<?php echo htmlspecialchars($player['jersey_number']); ?></span></td>
                                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($player['position']); ?></span></td>
                                                <td><?php echo $player['date_of_birth'] ? date_diff(date_create($player['date_of_birth']), date_create('today'))->y . ' yrs' : 'N/A'; ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($player['gender'] ?? 'N/A')); ?></td>
                                                <td>
                                                    <small>
                                                        <?php if ($player['height_cm']): ?><?php echo htmlspecialchars($player['height_cm']); ?>cm<br><?php endif; ?>
                                                        <?php if ($player['weight_kg']): ?><?php echo htmlspecialchars($player['weight_kg']); ?>kg<?php endif; ?>
                                                    </small>
                                                </td>
                                                <td><?php echo ucfirst(htmlspecialchars($player['preferred_foot'] ?? 'N/A')); ?></td>
                                                <td>
                                                    <small>
                                                        <?php if ($player['email']): ?><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($player['email']); ?><br><?php endif; ?>
                                                        <?php if ($player['phone']): ?><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($player['phone']); ?><?php endif; ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($player['id_number']); ?></td>
                                                <td>
                                                    <?php if ($player['id_photo_front'] || $player['id_photo_back']): ?>
                                                        <div class="d-flex flex-column gap-1">
                                                            <?php if ($player['id_photo_front']): ?>
                                                                <a href="#" class="image-modal-trigger" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="../../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" data-img-title="ID Front: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                    <img src="../../<?php echo htmlspecialchars($player['id_photo_front']); ?>?v=<?php echo time(); ?>" alt="ID Front" style="width: 50px; height: 50px; border-radius: 5px; object-fit: cover;">
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($player['id_photo_back']): ?>
                                                                <a href="#" class="image-modal-trigger" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="../../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" data-img-title="ID Back: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>">
                                                                    <img src="../../<?php echo htmlspecialchars($player['id_photo_back']); ?>?v=<?php echo time(); ?>" alt="ID Back" style="width: 50px; height: 50px; border-radius: 5px; object-fit: cover;">
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted">No ID photos</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="../players/view_player.php?id=<?php echo $player['id']; ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                                    <a href="../players/manage_player.php?id=<?php echo $player['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="modalImage" class="img-fluid" alt="Full size image">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var imageModal = document.getElementById('imageModal');
    imageModal.addEventListener('show.bs.modal', function (event) {
        var triggerElement = event.relatedTarget;
        var imageSrc = triggerElement.getAttribute('data-img-src');
        var imageTitle = triggerElement.getAttribute('data-img-title') || 'Photo';
        var modalImage = document.getElementById('modalImage');
        var modalTitle = document.getElementById('imageModalLabel');
        
        modalImage.src = imageSrc;
        modalTitle.textContent = imageTitle;
    });
});
</script>
</body>
</html>