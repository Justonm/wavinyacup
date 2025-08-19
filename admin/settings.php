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
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action'] ?? '');

    if ($action === 'general_settings') {
        // In a real application, you would update the database or a settings file here.
        // For this example, we'll just show a success message.
        $app_name = sanitize_input($_POST['app_name']);
        $app_url = sanitize_input($_POST['app_url']);
        $app_email = sanitize_input($_POST['app_email']);
        $app_timezone = sanitize_input($_POST['app_timezone']);
        $message = 'General settings saved successfully!';
        // Example: file_put_contents('../config/settings.json', json_encode($_POST));
    } elseif ($action === 'email_settings') {
        $smtp_host = sanitize_input($_POST['smtp_host']);
        $smtp_port = sanitize_input($_POST['smtp_port']);
        $smtp_username = sanitize_input($_POST['smtp_username']);
        $smtp_password = sanitize_input($_POST['smtp_password']);
        $message = 'Email settings saved successfully!';
    } elseif ($action === 'security_settings') {
        $debug_mode = isset($_POST['debug_mode']) ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $session_timeout = sanitize_input($_POST['session_timeout']);
        $message = 'Security settings saved successfully!';
    } elseif ($action === 'restore_backup' && !empty($_FILES['backup_file']['tmp_name'])) {
        if ($_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_file = $_FILES['backup_file']['tmp_name'];
            restoreDatabase($uploaded_file);
        } else {
            $error = "File upload failed. Error code: " . $_FILES['backup_file']['error'];
        }
    }
}

// Handle backup and restore actions
if (isset($_GET['action'])) {
    $action = sanitize_input($_GET['action']);

    if ($action === 'create_backup') {
        backupDatabase();
        exit;
    }
}

function backupDatabase() {
    $db = db();
    $tables = $db->fetchAll("SHOW TABLES");
    $backup_content = '';

    foreach ($tables as $table) {
        $table_name = array_values($table)[0];
        $create_table = $db->fetchRow("SHOW CREATE TABLE `{$table_name}`")['Create Table'];
        $backup_content .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
        $backup_content .= $create_table . ";\n\n";

        $rows = $db->fetchAll("SELECT * FROM `{$table_name}`");
        foreach ($rows as $row) {
            $row_data = array_map(function($value) use ($db) {
                return $db->quote($value);
            }, array_values($row));
            $backup_content .= "INSERT INTO `{$table_name}` VALUES (" . implode(',', $row_data) . ");\n";
        }
        $backup_content .= "\n";
    }

    $backupFile = 'backup_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $backupFile . '"');
    echo $backup_content;
    exit;
}

function restoreDatabase($file) {
    global $db, $message, $error;
    $sql_content = file_get_contents($file);
    if ($sql_content === false) {
        $error = "Failed to read the uploaded file.";
        return;
    }

    // A very simple SQL parser - not robust for all cases
    $queries = explode(";\n", $sql_content);
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query !== '') {
            try {
                $db->exec($query);
            } catch (Exception $e) {
                $error = "Error restoring database: " . $e->getMessage();
                return;
            }
        }
    }
    $message = "Database restored successfully!";
}

// Fetch current values (placeholders for now)
$app_name = defined('APP_NAME') ? APP_NAME : 'Governor Wavinya Cup';
$app_url = defined('APP_URL') ? APP_URL : '';
$app_email = defined('APP_EMAIL') ? APP_EMAIL : '';
$app_timezone = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Africa/Nairobi';
$smtp_host = defined('SMTP_HOST') ? SMTP_HOST : '';
$smtp_port = defined('SMTP_PORT') ? SMTP_PORT : '';
$smtp_username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
$smtp_password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
$debug_mode = defined('DEBUG_MODE') ? DEBUG_MODE : false;
$maintenance_mode = defined('MAINTENANCE_MODE') ? MAINTENANCE_MODE : false;
$session_timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
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
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link" href="teams.php"><i class="fas fa-users me-2"></i>Teams</a>
                    <a class="nav-link" href="players.php"><i class="fas fa-user me-2"></i>Players</a>
                    <a class="nav-link" href="coaches.php"><i class="fas fa-chalkboard-teacher me-2"></i>Coaches</a>
                    <a class="nav-link" href="registrations.php"><i class="fas fa-clipboard-list me-2"></i>Registrations</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                    <a class="nav-link active" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </nav>
            </div>
        </div>

        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-cog me-2"></i>System Settings</h2>
                        <p class="text-muted">Configure system preferences and manage users</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

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
                            <div class="tab-pane fade show active" id="general">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">General Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="settings.php">
                                            <input type="hidden" name="action" value="general_settings">
                                            <div class="mb-3">
                                                <label class="form-label">Application Name</label>
                                                <input type="text" name="app_name" class="form-control" value="<?php echo $app_name; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Application URL</label>
                                                <input type="url" name="app_url" class="form-control" value="<?php echo $app_url; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Contact Email</label>
                                                <input type="email" name="app_email" class="form-control" value="<?php echo $app_email; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Timezone</label>
                                                <select name="app_timezone" class="form-select">
                                                    <option value="Africa/Nairobi" <?php echo ($app_timezone === 'Africa/Nairobi') ? 'selected' : ''; ?>>Africa/Nairobi</option>
                                                    <option value="UTC" <?php echo ($app_timezone === 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="users">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">User Management</h5>
                                    </div>
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5>User Management</h5>
                                        <p class="text-muted">Manage system users and permissions</p>
                                        <a href="users.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add New User
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="email">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Email Configuration</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="settings.php">
                                            <input type="hidden" name="action" value="email_settings">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" name="smtp_host" class="form-control" value="<?php echo $smtp_host; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" name="smtp_port" class="form-control" value="<?php echo $smtp_port; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Username</label>
                                                <input type="text" name="smtp_username" class="form-control" value="<?php echo $smtp_username; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Password</label>
                                                <input type="password" name="smtp_password" class="form-control" value="<?php echo $smtp_password; ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

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
                                                        <a href="?action=create_backup" class="btn btn-light">Create Backup</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body text-center">
                                                        <i class="fas fa-upload fa-2x mb-3"></i>
                                                        <h5>Restore Backup</h5>
                                                        <p>Restore system from a backup file</p>
                                                        <form method="POST" action="settings.php" enctype="multipart/form-data">
                                                            <input type="hidden" name="action" value="restore_backup">
                                                            <input type="file" name="backup_file" class="form-control mb-2" required>
                                                            <button type="submit" class="btn btn-light">Restore Backup</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="security">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Security Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="settings.php">
                                            <input type="hidden" name="action" value="security_settings">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="debug_mode" id="debugMode" <?php echo ($debug_mode) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="debugMode">Debug Mode</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" <?php echo ($maintenance_mode) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Session Timeout (minutes)</label>
                                                <input type="number" name="session_timeout" class="form-control" value="<?php echo $session_timeout; ?>">
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