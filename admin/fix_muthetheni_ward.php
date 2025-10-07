<?php
// admin/fix_muthetheni_ward.php - Move Muthetheni ward to correct sub-county
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

// Get Mwala sub-county ID
$mwala = $db->fetchRow("SELECT * FROM sub_counties WHERE name = 'Mwala'");
if (!$mwala) {
    $error = 'Mwala sub-county not found in database.';
}

// Get current Muthetheni ward details
$muthetheni = $db->fetchRow("SELECT * FROM wards WHERE name = 'Muthetheni'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_ward']) && $mwala) {
    try {
        // Update Muthetheni ward to be in Mwala sub-county
        $db->query(
            "UPDATE wards SET sub_county_id = ?, code = ? WHERE name = 'Muthetheni'",
            [$mwala['id'], 'MUTH_MWL']
        );
        
        $message = "Successfully moved Muthetheni ward to Mwala sub-county!";
        
        // Refresh the ward details
        $muthetheni = $db->fetchRow("SELECT * FROM wards WHERE name = 'Muthetheni'");
        
    } catch (Exception $e) {
        $error = 'Error updating ward: ' . $e->getMessage();
    }
}

// Get all sub-counties for reference
$sub_counties = $db->fetchAll("SELECT * FROM sub_counties ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Muthetheni Ward - Admin</title>
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
                                <li class="breadcrumb-item active">Fix Muthetheni Ward</li>
                            </ol>
                        </nav>
                        <h2><i class="fas fa-tools me-2"></i>Fix Muthetheni Ward Location</h2>
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
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-map-marker-alt me-2"></i>Current Ward Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($muthetheni): ?>
                                    <?php 
                                    $current_sub_county = $db->fetchRow("SELECT name FROM sub_counties WHERE id = ?", [$muthetheni['sub_county_id']]);
                                    ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Current Details:</h6>
                                            <ul class="list-unstyled">
                                                <li><strong>Ward Name:</strong> <?php echo htmlspecialchars($muthetheni['name']); ?></li>
                                                <li><strong>Ward Code:</strong> <?php echo htmlspecialchars($muthetheni['code']); ?></li>
                                                <li><strong>Current Sub-County:</strong> 
                                                    <span class="badge bg-warning"><?php echo htmlspecialchars($current_sub_county['name']); ?></span>
                                                </li>
                                                <li><strong>Ward ID:</strong> <?php echo $muthetheni['id']; ?></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Correct Details:</h6>
                                            <ul class="list-unstyled">
                                                <li><strong>Ward Name:</strong> Muthetheni</li>
                                                <li><strong>New Ward Code:</strong> MUTH_MWL</li>
                                                <li><strong>Correct Sub-County:</strong> 
                                                    <span class="badge bg-success">Mwala</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <?php if ($mwala && $current_sub_county['name'] !== 'Mwala'): ?>
                                        <div class="mt-4">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Issue:</strong> Muthetheni ward is currently assigned to 
                                                <strong><?php echo htmlspecialchars($current_sub_county['name']); ?></strong> 
                                                but should be in <strong>Mwala</strong> sub-county.
                                            </div>
                                            
                                            <form method="POST">
                                                <input type="hidden" name="fix_ward" value="1">
                                                <button type="submit" class="btn btn-primary btn-lg" 
                                                        onclick="return confirm('Are you sure you want to move Muthetheni ward to Mwala sub-county?')">
                                                    <i class="fas fa-tools me-2"></i>Fix Ward Location
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($current_sub_county['name'] === 'Mwala'): ?>
                                        <div class="alert alert-success mt-4">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Correct!</strong> Muthetheni ward is now properly assigned to Mwala sub-county.
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Muthetheni ward not found in database.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Reference Information -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="fas fa-list me-2"></i>All Sub-Counties</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($sub_counties as $sc): ?>
                                        <div class="col-md-3 mb-2">
                                            <span class="badge bg-<?php echo $sc['name'] === 'Mwala' ? 'success' : 'secondary'; ?> fs-6">
                                                <?php echo htmlspecialchars($sc['name']); ?> (ID: <?php echo $sc['id']; ?>)
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i>About This Fix</h5>
                            </div>
                            <div class="card-body">
                                <p>This tool will:</p>
                                <ul>
                                    <li>Move Muthetheni ward from its current sub-county to Mwala</li>
                                    <li>Update the ward code to reflect the correct location</li>
                                    <li>Maintain all existing relationships and data</li>
                                </ul>
                                
                                <div class="mt-3">
                                    <a href="coaches/manage_coach.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user me-2"></i>Back to Coach Management
                                    </a>
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
