<?php
require_once '../config/config.php';

// Check if user has admin permissions
if (!has_permission('all')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-futbol me-2"></i>Machakos Teams
                        </h4>
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
                        <a class="nav-link" href="coaches.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Coaches
                        </a>
                        <a class="nav-link" href="registrations.php">
                            <i class="fas fa-clipboard-list me-2"></i>Registrations
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-cog me-2"></i>System Settings</h2>
                            <p class="text-muted">Configure system preferences and manage users</p>
                        </div>
                    </div>
                    
                    <!-- Settings Tabs -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="list-group">
                                <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                    <i class="fas fa-cog me-2"></i>General Settings
                                </a>
                                <a href="#users" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                    <i class="fas fa-users me-2"></i>User Management
                                </a>
                                <a href="#email" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                    <i class="fas fa-envelope me-2"></i>Email Settings
                                </a>
                                <a href="#backup" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                    <i class="fas fa-database me-2"></i>Backup & Restore
                                </a>
                                <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                    <i class="fas fa-shield-alt me-2"></i>Security
                                </a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="tab-content">
                                <!-- General Settings -->
                                <div class="tab-pane fade show active" id="general">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">General Settings</h5>
                                        </div>
                                        <div class="card-body">
                                            <form>
                                                <div class="mb-3">
                                                    <label class="form-label">Application Name</label>
                                                    <input type="text" class="form-control" value="<?php echo APP_NAME; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Application URL</label>
                                                    <input type="url" class="form-control" value="<?php echo APP_URL; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Contact Email</label>
                                                    <input type="email" class="form-control" value="<?php echo APP_EMAIL; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Timezone</label>
                                                    <select class="form-select">
                                                        <option value="Africa/Nairobi" selected>Africa/Nairobi</option>
                                                        <option value="UTC">UTC</option>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- User Management -->
                                <div class="tab-pane fade" id="users">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">User Management</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center py-5">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <h5>User Management</h5>
                                                <p class="text-muted">Manage system users and permissions</p>
                                                <button class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>Add New User
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Email Settings -->
                                <div class="tab-pane fade" id="email">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Email Configuration</h5>
                                        </div>
                                        <div class="card-body">
                                            <form>
                                                <div class="mb-3">
                                                    <label class="form-label">SMTP Host</label>
                                                    <input type="text" class="form-control" value="<?php echo SMTP_HOST; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">SMTP Port</label>
                                                    <input type="number" class="form-control" value="<?php echo SMTP_PORT; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">SMTP Username</label>
                                                    <input type="text" class="form-control" value="<?php echo SMTP_USERNAME; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">SMTP Password</label>
                                                    <input type="password" class="form-control" value="<?php echo SMTP_PASSWORD; ?>">
                                                </div>
                                                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Backup & Restore -->
                                <div class="tab-pane fade" id="backup">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Backup & Restore</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card bg-primary text-white">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-download fa-2x mb-3"></i>
                                                            <h5>Create Backup</h5>
                                                            <p>Generate a complete backup of the system</p>
                                                            <button class="btn btn-light">Create Backup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card bg-success text-white">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-upload fa-2x mb-3"></i>
                                                            <h5>Restore Backup</h5>
                                                            <p>Restore system from a backup file</p>
                                                            <button class="btn btn-light">Restore Backup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Security -->
                                <div class="tab-pane fade" id="security">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Security Settings</h5>
                                        </div>
                                        <div class="card-body">
                                            <form>
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="debugMode">
                                                        <label class="form-check-label" for="debugMode">
                                                            Debug Mode
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                                        <label class="form-check-label" for="maintenanceMode">
                                                            Maintenance Mode
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Session Timeout (minutes)</label>
                                                    <input type="number" class="form-control" value="60">
                                                </div>
                                                <button type="submit" class="btn btn-primary">Save Security Settings</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 