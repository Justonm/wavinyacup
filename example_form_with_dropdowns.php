<?php
/**
 * Example Registration Form with Prefilled Location Dropdowns
 * This demonstrates how to use the location helper functions
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/location_helpers.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get location data for dropdowns
$sub_counties = get_all_sub_counties();
$wards = get_all_wards();

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sub_county_id = (int)($_POST['sub_county_id'] ?? 0);
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    
    // Validation
    if ($sub_county_id <= 0) {
        $error = 'Please select a sub county.';
    } elseif ($ward_id <= 0) {
        $error = 'Please select a ward.';
    } else {
        // Verify ward belongs to selected sub county
        $ward_check = get_wards_by_sub_county($sub_county_id);
        $valid_ward = false;
        foreach ($ward_check as $ward) {
            if ($ward['id'] == $ward_id) {
                $valid_ward = true;
                break;
            }
        }
        
        if (!$valid_ward) {
            $error = 'Selected ward does not belong to the selected sub county.';
        } else {
            $success = 'Form submitted successfully! Sub County ID: ' . $sub_county_id . ', Ward ID: ' . $ward_id;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Dropdown Example</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Location Selection Example</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Method 1: Using helper functions -->
                            <div class="mb-3">
                                <label for="sub_county_id" class="form-label">Sub County</label>
                                <?php echo generate_sub_county_dropdown($_POST['sub_county_id'] ?? ''); ?>
                            </div>

                            <div class="mb-3">
                                <label for="ward_id" class="form-label">Ward</label>
                                <?php echo generate_ward_dropdown($_POST['ward_id'] ?? ''); ?>
                            </div>

                            <!-- Method 2: Manual dropdown with data -->
                            <div class="mb-3">
                                <label for="sub_county_id_manual" class="form-label">Sub County (Manual)</label>
                                <select class="form-control" id="sub_county_id_manual" name="sub_county_id_manual" onchange="filterWardsManual()">
                                    <option value="">Select Sub County</option>
                                    <?php foreach ($sub_counties as $sub_county): ?>
                                        <option value="<?php echo $sub_county['id']; ?>" 
                                                <?php echo (($_POST['sub_county_id_manual'] ?? '') == $sub_county['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sub_county['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="ward_id_manual" class="form-label">Ward (Manual)</label>
                                <select class="form-control" id="ward_id_manual" name="ward_id_manual">
                                    <option value="">Select Ward</option>
                                    <?php foreach ($wards as $ward): ?>
                                        <option value="<?php echo $ward['id']; ?>" 
                                                data-sub-county="<?php echo $ward['sub_county_id']; ?>"
                                                <?php echo (($_POST['ward_id_manual'] ?? '') == $ward['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ward['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the ward filtering JavaScript -->
    <?php echo generate_ward_filter_js(); ?>
    
    <script>
        // Manual filtering function for the second set of dropdowns
        function filterWardsManual() {
            const subCountySelect = document.getElementById('sub_county_id_manual');
            const wardSelect = document.getElementById('ward_id_manual');
            const selectedSubCounty = subCountySelect.value;
            
            // Show/hide ward options based on sub county selection
            const wardOptions = wardSelect.querySelectorAll('option');
            
            wardOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block'; // Always show "Select Ward" option
                } else {
                    const wardSubCounty = option.getAttribute('data-sub-county');
                    if (!selectedSubCounty || wardSubCounty === selectedSubCounty) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
            
            // Reset ward selection when sub county changes
            wardSelect.value = '';
        }
    </script>
</body>
</html>
