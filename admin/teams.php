<?php
// Include all necessary configuration and helper files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// Check if user is logged in and has admin permissions
if (!is_logged_in() || !has_role('admin')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Handle search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = " WHERE (t.name LIKE ? OR t.team_code LIKE ? OR w.name LIKE ? OR sc.name LIKE ?)";
    $searchTerm = "%$search%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }

// Total count for pagination
$totalTeams = (int) $db->fetchCell("
    SELECT COUNT(*)
    FROM teams t
    JOIN wards w ON t.ward_id = w.id
    JOIN sub_counties sc ON w.sub_county_id = sc.id
    $searchCondition
", $searchParams);

$totalPages = max(1, (int)ceil($totalTeams / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;

// Get teams with ward and sub-county information (paginated)
$limit = (int)$perPage;
$offsetInt = (int)$offset;
$teams = $db->fetchAll("
    SELECT t.*, w.name as ward_name, sc.name as sub_county_name 
    FROM teams t 
    JOIN wards w ON t.ward_id = w.id 
    JOIN sub_counties sc ON w.sub_county_id = sc.id 
    $searchCondition
    ORDER BY t.created_at DESC
    LIMIT $limit OFFSET $offsetInt
", $searchParams);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $page_title = 'Teams Management'; include dirname(__DIR__) . '/includes/head.php'; ?>
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
                            <h2><i class="fas fa-users me-2"></i>Teams Management</h2>
                            <p class="text-muted">Manage all registered teams in the system.</p>
                        </div>
                        <a href="teams/register.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Team
                        </a>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-10">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Search teams by name, code, ward, or sub-county..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Search
                                    </button>
                                </div>
                            </form>
                            <?php if (!empty($search)): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                                        <a href="teams.php" class="ms-2 text-decoration-none">
                                            <i class="fas fa-times"></i> Clear search
                                        </a>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($teams)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No Teams Found</h5>
                                    <p class="text-muted">No teams have been registered yet. Get started by adding one.</p>
                                    <a href="teams/manage_team.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-2"></i>Register First Team
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th></th>
                                                <th>Team Name</th>
                                                <th>Ward</th>
                                                <th>Sub-County</th>
                                                <th>Status</th>
                                                <th>Registered On</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teams as $idx => $team): ?>
                                                <tr>
                                                    <td><?php echo ($offset + $idx + 1); ?></td>
                                                    <td>
                                                        <?php if (!empty($team['team_photo'])): ?>
                                                            <?php 
                                                            // Handle different possible image path formats
                                                            $image_path = $team['team_photo'];
                                                            if (strpos($image_path, 'uploads/') === 0) {
                                                                // Path already includes uploads/
                                                                $full_image_url = APP_URL . '/' . $image_path;
                                                            } else {
                                                                // Path doesn't include uploads/, add it
                                                                $full_image_url = APP_URL . '/uploads/' . $image_path;
                                                            }
                                                            ?>
                                                            <img src="<?php echo $full_image_url; ?>" alt="<?php echo htmlspecialchars($team['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <div class="text-center text-muted align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa; border-radius: .25rem; display: none;">
                                                                <i class="fas fa-users"></i>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-center text-muted d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa; border-radius: .25rem;">
                                                                <i class="fas fa-users"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                                        <div class="text-muted small">Code: <?php echo htmlspecialchars($team['team_code']); ?></div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($team['ward_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($team['sub_county_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $team['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($team['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo format_date($team['created_at']); ?></td>
                                                    <td>
                                                        <a href="teams/view_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Team Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="teams/manage_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Team">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger" title="Delete Team (Not Implemented)" disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                                    // Pagination controls
                                    $showingFrom = $totalTeams > 0 ? $offset + 1 : 0;
                                    $showingTo = min($offset + $perPage, $totalTeams);
                                    // Build base query params preserving search
                                    $baseParams = [];
                                    if (!empty($search)) { $baseParams['search'] = $search; }
                                ?>
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                    <div class="text-muted small">
                                        Showing <?php echo $showingFrom; ?> to <?php echo $showingTo; ?> of <?php echo $totalTeams; ?> teams
                                    </div>
                                    <nav aria-label="Teams pagination">
                                        <ul class="pagination mb-0">
                                            <?php
                                                $prevDisabled = $page <= 1 ? ' disabled' : '';
                                                $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                                                // Previous link
                                                $prevParams = $baseParams;
                                                $prevParams['page'] = max(1, $page - 1);
                                                $nextParams = $baseParams;
                                                $nextParams['page'] = min($totalPages, $page + 1);
                                            ?>
                                            <li class="page-item<?php echo $prevDisabled; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query($prevParams); ?>" tabindex="-1">&laquo;</a>
                                            </li>
                                            <?php
                                                // Determine window of pages to show
                                                $window = 5; // show up to 5 page numbers
                                                $start = max(1, $page - 2);
                                                $end = min($totalPages, $start + $window - 1);
                                                // Adjust start if we don't have enough at the end
                                                $start = max(1, min($start, $end - $window + 1));
                                                for ($p = $start; $p <= $end; $p++) {
                                                    $params = $baseParams; $params['page'] = $p;
                                                    $active = $p == $page ? ' active' : '';
                                                    echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($params)) . '">' . $p . '</a></li>';
                                                }
                                            ?>
                                            <li class="page-item<?php echo $nextDisabled; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query($nextParams); ?>">&raquo;</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>