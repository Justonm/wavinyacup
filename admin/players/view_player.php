<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_logged_in() || !has_role('admin')) {
    redirect('../../auth/login.php');
}

$db = db();
$player_id = $_GET['id'] ?? null;

if (!$player_id) {
    redirect('../teams.php');
}

// Fetch player details along with team, ward, and sub-county information
$player = $db->fetchRow("
    SELECT p.*, t.name as team_name, w.name as ward_name, sc.name as sub_county_name
    FROM players p
    JOIN teams t ON p.team_id = t.id
    JOIN wards w ON t.ward_id = w.id
    JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE p.id = ?
", [$player_id]);

if (!$player) {
    $_SESSION['error_message'] = 'Player not found.';
    redirect('../teams.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Player: <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
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
                            <h2><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h2>
                            <p class="text-muted">Player Details</p>
                        </div>
                        <div>
                            <a href="../teams/view_team.php?id=<?php echo $player['team_id']; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Team</a>
                            <a href="manage_player.php?id=<?php echo $player_id; ?>" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Edit Player</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Player Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img src="<?php echo BASE_URL . '/uploads/players/' . ($player['photo_path'] ?? 'default.png'); ?>" alt="Player Photo" class="img-thumbnail mb-3" style="max-width: 200px;">
                                    <img src="<?php echo BASE_URL . '/uploads/id/' . ($player['id_document_path'] ?? 'default.png'); ?>" alt="ID Document" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                                <div class="col-md-8">
                                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></p>
                                    <p><strong>ID Number:</strong> <?php echo htmlspecialchars($player['id_number']); ?></p>
                                    <p><strong>Position:</strong> <?php echo htmlspecialchars($player['position']); ?></p>
                                    <p><strong>Jersey Number:</strong> <?php echo htmlspecialchars($player['jersey_number']); ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-<?php echo $player['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $player['is_active'] ? 'Active' : 'Inactive'; ?></span></p>
                                    <hr>
                                    <p><strong>Team:</strong> <?php echo htmlspecialchars($player['team_name']); ?></p>
                                    <p><strong>Ward:</strong> <?php echo htmlspecialchars($player['ward_name']); ?></p>
                                    <p><strong>Sub-County:</strong> <?php echo htmlspecialchars($player['sub_county_name']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
