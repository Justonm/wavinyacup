<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Authenticate and authorize viewer
if (!is_logged_in() || !has_permission('view_all_teams')) {
    redirect(app_base_url() . '/auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get teams with ward, sub-county, coach, and player count information
$teams = $db->fetchAll("
    SELECT 
        t.*, 
        w.name as ward_name, 
        sc.name as sub_county_name, 
        CONCAT(u.first_name, ' ', u.last_name) as coach_name,
        (SELECT COUNT(*) FROM players p WHERE p.team_id = t.id AND p.is_active = 1) as player_count
    FROM teams t 
    LEFT JOIN wards w ON t.ward_id = w.id 
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    LEFT JOIN coaches c ON t.id = c.team_id
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY t.name ASC
");

$page_title = 'View Teams';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include dirname(__DIR__) . '/includes/head.php'; ?>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div class="content-area">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col">
                        <h2 class="mt-4">All Teams</h2>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" id="teamsSearch" class="form-control" placeholder="Search teams... (name, coach, players, ward, sub-county, status)">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="teamsTable">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Team Name</th>
                                        <th>Coach</th>
                                        <th>Players</th>
                                        <th>Ward</th>
                                        <th>Sub-County</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teams as $team): ?>
                                        <tr>
                                            <td>
                                                <?php if ($team['team_photo']): ?>
                                                    <img src="../<?php echo htmlspecialchars($team['team_photo']); ?>" alt="Team photo" style="width: 50px; height: 50px; object-fit: cover;" class="rounded-circle">
                                                <?php else: ?>
                                                    <i class="fas fa-shield-alt fa-2x text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($team['name']); ?></td>
                                            <td><?php echo htmlspecialchars($team['coach_name'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($team['player_count']); ?> / 22</span></td>
                                            <td><?php echo htmlspecialchars($team['ward_name']); ?></td>
                                            <td><?php echo htmlspecialchars($team['sub_county_name']); ?></td>
                                            <td><span class="badge bg-<?php echo $team['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($team['status']); ?></span></td>
                                            <td>
                                                <a href="view_team_details.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            const input = document.getElementById('teamsSearch');
            const table = document.getElementById('teamsTable');
            if (!input || !table) return;
            const tbody = table.querySelector('tbody');
            input.addEventListener('input', function(){
                const q = this.value.toLowerCase();
                Array.from(tbody.rows).forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(q) ? '' : 'none';
                });
            });
        })();
    </script>
</body>
</html>
