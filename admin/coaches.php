<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../auth/gmail_oauth.php';

// Check if user has admin permissions using the new GmailOAuth class
if (!GmailOAuth::isValidAdminSession()) {
    // Redirect to the admin login page
    redirect('../auth/admin_login.php');
}

$user = get_logged_in_user();
$db = db();

// Get comprehensive coach details with team information and images
$query = "
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
        c.license_number,
        c.license_type,
        c.experience_years,
        c.specialization,
        c.certifications,
        c.coach_image,
        t.name as team_name,
        t.team_code,
        w.name as ward_name,
        sc.name as sub_county_name
    FROM users u
    LEFT JOIN coaches c ON u.id = c.user_id
    LEFT JOIN teams t ON c.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    WHERE u.role = 'coach' AND u.approval_status = 'approved'
    ORDER BY u.created_at DESC
";

// Execute the query and check for success
$stmt = $db->query($query);

// Check if the query was successful
if ($stmt) {
    $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // If the query failed, initialize an empty array to prevent errors later
    $coaches = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Coaches Management'; include dirname(__DIR__) . '/includes/head.php'; ?>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .btn {
            cursor: pointer;
            pointer-events: auto;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar p-3">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo.png" alt="Governor Wavinya Cup 3rd Edition Logo" style="width: 120px; height: auto;" class="mb-2">
                    <h5 class="text-white mb-0">Governor Wavinya Cup 3rd Edition</h5>
                    <small class="text-white-50">Admin Dashboard</small>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="teams.php">
                        <i class="fas fa-users me-2"></i>Teams
                    </a>
                    <a class="nav-link" href="players.php">
                        <i class="fas fa-user me-2"></i>Players
                    </a>
                    <a class="nav-link active" href="coaches.php">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Coaches
                    </a>
                    <a class="nav-link" href="registrations.php">
                        <i class="fas fa-clipboard-list me-2"></i>Registrations
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-chalkboard-teacher me-2"></i>Coaches Management</h2>
                        <p class="text-muted">Manage all registered coaches</p>
                    </div>
                    <a href="../coaches/register.php" class="btn btn-primary" onclick="console.log('Add New Coach clicked')">
                        <i class="fas fa-plus me-2"></i>Add New Coach
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Coaches</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($coaches)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                                <h5>No Coaches Found</h5>
                                <p class="text-muted">No coaches have been registered yet.</p>
                                <a href="../coaches/register.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Register First Coach
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($coaches as $coach): ?>
                                    <div class="col-lg-6 col-xl-4 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start mb-3">
                                                    <div class="flex-shrink-0 me-3">
                                                        <?php 
                                                        $coach_image = $coach['coach_image'] ?: $coach['profile_image'];
                                                        if ($coach_image): 
                                                        ?>
                                                            <img src="../<?php echo htmlspecialchars($coach_image); ?>" 
                                                                 alt="Coach Photo" 
                                                                 class="rounded-circle" 
                                                                 style="width: 80px; height: 80px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                                                 style="width: 80px; height: 80px; font-size: 24px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="card-title mb-1">
                                                            <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?>
                                                        </h5>
                                                        <p class="text-muted small mb-2">
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <?php echo htmlspecialchars($coach['email']); ?>
                                                        </p>
                                                        <span class="badge bg-<?php echo $coach['is_active'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $coach['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Phone</small>
                                                        <span class="fw-medium"><?php echo htmlspecialchars($coach['phone'] ?: 'N/A'); ?></span>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">ID Number</small>
                                                        <span class="fw-medium"><?php echo htmlspecialchars($coach['id_number'] ?: 'N/A'); ?></span>
                                                    </div>
                                                </div>

                                                <?php if ($coach['team_name']): ?>
                                                <div class="mb-3">
                                                    <small class="text-muted d-block">Team</small>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-users text-primary me-2"></i>
                                                        <span class="fw-medium"><?php echo htmlspecialchars($coach['team_name']); ?></span>
                                                        <?php if ($coach['team_code']): ?>
                                                            <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($coach['team_code']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($coach['ward_name']): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($coach['ward_name']); ?>
                                                            <?php if ($coach['sub_county_name']): ?>
                                                                , <?php echo htmlspecialchars($coach['sub_county_name']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>

                                                <div class="row g-2 mb-3">
                                                    <?php if ($coach['license_number']): ?>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">License</small>
                                                        <span class="fw-medium"><?php echo htmlspecialchars($coach['license_number']); ?></span>
                                                        <br><small class="text-capitalize"><?php echo htmlspecialchars($coach['license_type'] ?: ''); ?></small>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($coach['experience_years']): ?>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Experience</small>
                                                        <span class="fw-medium"><?php echo htmlspecialchars($coach['experience_years']); ?> years</span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($coach['specialization']): ?>
                                                <div class="mb-3">
                                                    <small class="text-muted d-block">Specialization</small>
                                                    <span class="fw-medium"><?php echo htmlspecialchars($coach['specialization']); ?></span>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($coach['certifications']): ?>
                                                <div class="mb-3">
                                                    <small class="text-muted d-block">Certifications</small>
                                                    <small><?php echo nl2br(htmlspecialchars($coach['certifications'])); ?></small>
                                                </div>
                                                <?php endif; ?>

                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Registered: <?php echo date('M j, Y', strtotime($coach['created_at'])); ?>
                                                    </small>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary flex-fill" 
                                                            onclick="viewCoach(<?php echo $coach['id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning flex-fill" 
                                                            onclick="editCoach(<?php echo $coach['id']; ?>)">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteCoach(<?php echo $coach['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Coaches page loaded');
    });

    function viewCoach(coachId) {
        // Redirect to coach details page
        window.location.href = `coaches/view_coach.php?id=${coachId}`;
    }

    function editCoach(coachId) {
        // Redirect to coach edit page
        window.location.href = `coaches/manage_coach.php?id=${coachId}`;
    }

    function deleteCoach(coachId) {
        if (confirm('Are you sure you want to delete this coach? This action cannot be undone.')) {
            // Send AJAX request to delete coach
            fetch('coaches/delete_coach.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: coachId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Coach deleted successfully');
                    location.reload();
                } else {
                    alert('Error deleting coach: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the coach');
            });
        }
    }
</script>
</body>
</html>