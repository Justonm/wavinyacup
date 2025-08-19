<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get coaches directly from the users table by filtering on the 'role' column
$query = "
    SELECT 
        id, 
        first_name, 
        last_name, 
        email, 
        phone,
        is_active
    FROM users 
    WHERE role = 'coach'
    ORDER BY created_at DESC
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coaches Management - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <img src="../assets/images/logo.png" alt="Governor Wavinya Cup Logo" style="width: 120px; height: auto;" class="mb-2">
                    <h5 class="text-white mb-0">Governor Wavinya Cup</h5>
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
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Coach Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coaches as $coach): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($coach['email']); ?></td>
                                                <td><?php echo htmlspecialchars($coach['phone']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $coach['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $coach['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Coaches page loaded');
    });
</script>
</body>
</html>