<?php
// admin/coaches/view_coach.php - View detailed coach information
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../auth/gmail_oauth.php';

// Check admin permissions
if (!GmailOAuth::isValidAdminSession()) {
    redirect('../../auth/admin_login.php');
}

$coach_id = (int)($_GET['id'] ?? 0);
if (!$coach_id) {
    redirect('../coaches.php');
}

$db = db();

// Get comprehensive coach details
$coach = $db->fetchRow("
    SELECT 
        u.id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.phone,
        u.id_number,
        u.profile_image,
        u.is_active,
        u.created_at,
        u.username,
        c.license_number,
        c.license_type,
        c.experience_years,
        c.specialization,
        c.certifications,
        c.coach_image,
        t.name as team_name,
        t.team_code,
        t.id as team_id,
        tw.name as team_ward_name,
        tsc.name as team_sub_county_name
    FROM users u
    LEFT JOIN coaches c ON u.id = c.user_id
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards tw ON t.ward_id = tw.id
    LEFT JOIN sub_counties tsc ON tw.sub_county_id = tsc.id
    WHERE u.id = ? AND u.role = 'coach'
", [$coach_id]);

// Get coach's personal ward information
$coach_ward = $db->fetchRow("
    SELECT cw.name as coach_ward_name, csc.name as coach_sub_county_name
    FROM coach_registrations cr
    LEFT JOIN wards cw ON cr.ward_id = cw.id
    LEFT JOIN sub_counties csc ON cw.sub_county_id = csc.id
    WHERE cr.user_id = ?
    ORDER BY cr.created_at DESC
    LIMIT 1
", [$coach_id]);

if (!$coach) {
    redirect('../coaches.php');
}

// Get team players if coach has a team
$players = [];
if ($coach['team_id']) {
    $players = $db->fetchAll("
        SELECT * FROM players 
        WHERE team_id = ? 
        ORDER BY jersey_number ASC
    ", [$coach['team_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Details - <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include '../sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="../coaches.php">Coaches</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></li>
                            </ol>
                        </nav>
                        <h2><i class="fas fa-user me-2"></i>Coach Details</h2>
                    </div>
                    <div>
                        <a href="manage_coach.php?id=<?php echo $coach['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Edit Coach
                        </a>
                        <a href="../coaches.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Coaches
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php 
                                $coach_image = $coach['coach_image'] ?: $coach['profile_image'];
                                if ($coach_image): 
                                ?>
                                    <img src="../../<?php echo htmlspecialchars($coach_image); ?>" 
                                         alt="Coach Photo" 
                                         class="rounded-circle mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto mb-3" 
                                         style="width: 150px; height: 150px; font-size: 48px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h4><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></h4>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($coach['email']); ?></p>
                                <span class="badge bg-<?php echo $coach['is_active'] ? 'success' : 'secondary'; ?> fs-6">
                                    <?php echo $coach['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i>Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Full Name</label>
                                        <p><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <p><?php echo htmlspecialchars($coach['email']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Phone</label>
                                        <p><?php echo htmlspecialchars($coach['phone'] ?: 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">ID Number</label>
                                        <p><?php echo htmlspecialchars($coach['id_number'] ?: 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Username</label>
                                        <p><?php echo htmlspecialchars($coach['username'] ?: 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Registration Date</label>
                                        <p><?php echo date('F j, Y g:i A', strtotime($coach['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-certificate me-2"></i>Coaching Credentials</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">License Number</label>
                                    <p><?php echo htmlspecialchars($coach['license_number'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">License Type</label>
                                    <p class="text-capitalize"><?php echo htmlspecialchars($coach['license_type'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Experience</label>
                                    <p><?php echo htmlspecialchars($coach['experience_years'] ?: '0'); ?> years</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Specialization</label>
                                    <p><?php echo htmlspecialchars($coach['specialization'] ?: 'N/A'); ?></p>
                                </div>
                                <?php if ($coach['certifications']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Certifications</label>
                                    <p><?php echo nl2br(htmlspecialchars($coach['certifications'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-map-marker-alt me-2"></i>Location Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Coach's Ward</label>
                                    <?php if ($coach_ward && $coach_ward['coach_ward_name']): ?>
                                        <p>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($coach_ward['coach_ward_name']); ?></span>
                                            <?php if ($coach_ward['coach_sub_county_name']): ?>
                                                , <?php echo htmlspecialchars($coach_ward['coach_sub_county_name']); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">No ward assigned</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users me-2"></i>Team Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($coach['team_name']): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Team Name</label>
                                        <p><?php echo htmlspecialchars($coach['team_name']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Team Code</label>
                                        <p><span class="badge bg-primary"><?php echo htmlspecialchars($coach['team_code']); ?></span></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Team Location</label>
                                        <p>
                                            <?php echo htmlspecialchars($coach['team_ward_name']); ?>
                                            <?php if ($coach['team_sub_county_name']): ?>
                                                , <?php echo htmlspecialchars($coach['team_sub_county_name']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Total Players</label>
                                        <p><?php echo count($players); ?> players</p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No team assigned yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($players)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-running me-2"></i>Team Players</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Jersey #</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Age</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($players as $player): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($player['jersey_number']); ?></span></td>
                                        <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($player['position'] ?: 'N/A'); ?></td>
                                        <td><?php echo $player['date_of_birth'] ? date_diff(date_create($player['date_of_birth']), date_create('today'))->y : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $player['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $player['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
