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

// Handle report generation
if (isset($_GET['report_type'])) {
    $report_type = sanitize_input($_GET['report_type']);
    $format = sanitize_input($_GET['format'] ?? 'excel'); // Default to Excel for downloads

    // Fetch comprehensive data for the report
    $report_data = $db->fetchAll("
        SELECT 
            t.name AS team_name,
            t.status AS team_status,
            sc.name AS sub_county_name,
            w.name AS ward_name,
            COUNT(p.id) AS player_count,
            CONCAT(u.first_name, ' ', u.last_name) AS coach_name
        FROM teams t
        LEFT JOIN wards w ON t.ward_id = w.id
        LEFT JOIN sub_counties sc ON t.sub_county_id = sc.id
        LEFT JOIN players p ON p.team_id = t.id
        LEFT JOIN users u ON u.team_id = t.id AND u.role = 'coach'
        GROUP BY t.id
        ORDER BY sub_county_name, team_name
    ");

    // Start report generation based on format
    if ($format === 'pdf') {
        // For a proper PDF, you need a library like FPDF or TCPDF.
        // This is a simple placeholder to inform the user.
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="report_info.txt"');
        echo "To generate a proper PDF report, you must first install a PHP PDF library (e.g., FPDF or TCPDF) and write the code to render the report data into a PDF format.";
        exit;
    }

    if ($format === 'excel') {
        // Set headers for a proper CSV file that Excel can open
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="teams_report_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Output the header row
        fputcsv($output, ['Team Name', 'Sub-County', 'Ward', 'Player Count', 'Coach Name', 'Status']);
        
        // Output the data rows
        foreach ($report_data as $row) {
            fputcsv($output, [
                $row['team_name'],
                $row['sub_county_name'],
                $row['ward_name'],
                $row['player_count'],
                $row['coach_name'],
                $row['team_status']
            ]);
        }
        fclose($output);
        exit; // Important: Stop the script after sending the file
    }
}

// Get statistics for the dashboard view
$total_teams = $db->fetchRow("SELECT COUNT(*) as count FROM teams WHERE status = 'active'")['count'] ?? 0;
$total_players = $db->fetchRow("SELECT COUNT(*) as count FROM players WHERE is_active = 1")['count'] ?? 0;
$total_coaches = $db->fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'coach' AND is_active = 1")['count'] ?? 0;
$pending_registrations = $db->fetchRow("SELECT COUNT(*) as count FROM team_registrations WHERE status = 'pending'")['count'] ?? 0;

// Get teams by sub-county for the chart
$teams_by_sub_county = $db->fetchAll("
    SELECT sc.name as sub_county, COUNT(t.id) as team_count 
    FROM sub_counties sc 
    LEFT JOIN wards w ON sc.id = w.sub_county_id 
    LEFT JOIN teams t ON w.id = t.ward_id AND t.status = 'active' 
    GROUP BY sc.id, sc.name 
    ORDER BY team_count DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Governor Wavinya Cup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #b71c1c);
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
                    <a class="nav-link" href="coaches.php">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Coaches
                    </a>
                    <a class="nav-link" href="registrations.php">
                        <i class="fas fa-clipboard-list me-2"></i>Registrations
                    </a>
                    <a class="nav-link active" href="reports.php">
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
                        <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                        <p class="text-muted">View system statistics and generate reports</p>
                    </div>
                    <a href="?report_type=teams&format=excel" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Export Full Report
                    </a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_teams; ?></h3>
                                        <p class="mb-0">Active Teams</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_players; ?></h3>
                                        <p class="mb-0">Registered Players</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_coaches; ?></h3>
                                        <p class="mb-0">Coaches</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="mb-0"><?php echo $pending_registrations; ?></h3>
                                        <p class="mb-0">Pending Approvals</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Teams by Sub-County
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="teamsChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="?report_type=teams&format=pdf" class="btn btn-outline-primary">
                                        <i class="fas fa-file-pdf me-2"></i>Generate PDF Report
                                    </a>
                                    <a href="?report_type=teams&format=excel" class="btn btn-outline-success">
                                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                                    </a>
                                    <a href="?report_type=analytics&format=json" class="btn btn-outline-info">
                                        <i class="fas fa-chart-pie me-2"></i>Detailed Analytics
                                    </a>
                                    <a href="?report_type=monthly&format=excel" class="btn btn-outline-warning">
                                        <i class="fas fa-calendar me-2"></i>Monthly Report
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
<script>
    // Teams by Sub-County Chart
    const ctx = document.getElementById('teamsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($teams_by_sub_county, 'sub_county')); ?>,
            datasets: [{
                label: 'Number of Teams',
                data: <?php echo json_encode(array_column($teams_by_sub_county, 'team_count')); ?>,
                backgroundColor: 'rgba(13, 71, 161, 0.8)',
                borderColor: 'rgba(13, 71, 161, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
</body>
</html>