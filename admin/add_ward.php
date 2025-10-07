<?php
// admin/add_ward.php - Add missing wards through admin interface
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../auth/gmail_oauth.php';

// Check admin permissions
if (!GmailOAuth::isValidAdminSession()) {
    redirect('../auth/admin_login.php');
}

$db = db();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ward_name = sanitize_input($_POST['ward_name']);
    $sub_county_id = (int)$_POST['sub_county_id'];
    $ward_code = sanitize_input($_POST['ward_code']);
    
    if (empty($ward_name) || $sub_county_id <= 0) {
        $error = 'Ward name and sub-county are required.';
    } else {
        try {
            // Check if ward already exists
            $existing = $db->fetchRow("SELECT * FROM wards WHERE name = ?", [$ward_name]);
            
            if ($existing) {
                $error = 'Ward already exists.';
            } else {
                // Generate code if not provided
                if (empty($ward_code)) {
                    $ward_code = strtoupper(substr($ward_name, 0, 4)) . '_' . str_pad($sub_county_id, 3, '0', STR_PAD_LEFT);
                }
                
                $db->query(
                    "INSERT INTO wards (sub_county_id, name, code) VALUES (?, ?, ?)",
                    [$sub_county_id, $ward_name, $ward_code]
                );
                
                $message = "Ward '{$ward_name}' added successfully!";
            }
        } catch (Exception $e) {
            $error = 'Error adding ward: ' . $e->getMessage();
        }
    }
}

// Get sub-counties for dropdown
$sub_counties = $db->fetchAll("SELECT * FROM sub_counties ORDER BY name");

// Get all wards
$wards = $db->fetchAll("
    SELECT w.*, sc.name as sub_county_name 
    FROM wards w 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    ORDER BY sc.name, w.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Ward - Admin</title>
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
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Add Ward</li>
                            </ol>
                        </nav>
                        <h2><i class="fas fa-map-marker-alt me-2"></i>Add Ward</h2>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus me-2"></i>Add New Ward</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="ward_name" class="form-label">Ward Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ward_name" name="ward_name" 
                                               value="<?php echo htmlspecialchars($_POST['ward_name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sub_county_id" class="form-label">Sub County <span class="text-danger">*</span></label>
                                        <select class="form-select" id="sub_county_id" name="sub_county_id" required>
                                            <option value="">Select Sub County</option>
                                            <?php foreach ($sub_counties as $sc): ?>
                                                <option value="<?php echo $sc['id']; ?>" 
                                                        <?php echo ($_POST['sub_county_id'] ?? '') == $sc['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sc['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="ward_code" class="form-label">Ward Code</label>
                                        <input type="text" class="form-control" id="ward_code" name="ward_code" 
                                               value="<?php echo htmlspecialchars($_POST['ward_code'] ?? ''); ?>"
                                               placeholder="Leave empty to auto-generate">
                                        <div class="form-text">If left empty, a code will be generated automatically</div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Add Ward
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Quick Add Muthetheni -->
                        <div class="card mt-4">
                            <div class="card-header bg-warning">
                                <h5><i class="fas fa-bolt me-2"></i>Quick Add: Muthetheni Ward</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="ward_name" value="Muthetheni">
                                    <input type="hidden" name="sub_county_id" value="1">
                                    <input type="hidden" name="ward_code" value="MUTH_001">
                                    
                                    <p>Add Muthetheni ward to <strong>Machakos Town</strong> sub-county?</p>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-plus me-2"></i>Add Muthetheni Ward
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list me-2"></i>Existing Wards</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table table-striped table-sm">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th>Ward Name</th>
                                                <th>Code</th>
                                                <th>Sub-County</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($wards as $ward): ?>
                                                <?php $highlight = ($ward['name'] === 'Muthetheni') ? 'table-warning' : ''; ?>
                                                <tr class="<?php echo $highlight; ?>">
                                                    <td><?php echo htmlspecialchars($ward['name']); ?></td>
                                                    <td><small><?php echo htmlspecialchars($ward['code']); ?></small></td>
                                                    <td><small><?php echo htmlspecialchars($ward['sub_county_name']); ?></small></td>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
