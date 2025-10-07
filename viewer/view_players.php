<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Authenticate and authorize viewer
if (!is_logged_in() || !has_permission('view_all_players')) {
    redirect(app_base_url() . '/auth/login.php');
}

$user = get_logged_in_user();
$db = db();

// Get all players with user, team, and location information
$players = $db->fetchAll("
    SELECT 
        p.id, 
        u.first_name, 
        u.last_name, 
        p.position, 
        p.jersey_number, 
        p.is_active, 
        p.player_image,
        p.gender,
        p.date_of_birth,
        t.name as team_name, 
        w.name as ward_name, 
        sc.name as sub_county_name
    FROM players p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN wards w ON t.ward_id = w.id
    LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
    ORDER BY u.last_name, u.first_name
");

$page_title = 'View Players';

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
                        <h2 class="mt-4">All Players</h2>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" id="playersSearch" class="form-control" placeholder="Search players... (name, team, position, jersey, age, gender, status)">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="playersTable">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Team</th>
                                        <th>Position</th>
                                        <th>Jersey #</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($players as $player): ?>
                                        <tr>
                                            <td>
                                                <?php if ($player['player_image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($player['player_image']); ?>" alt="Player photo" style="width: 40px; height: 40px; object-fit: cover;" class="rounded-circle">
                                                <?php else: ?>
                                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($player['team_name'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($player['position']); ?></span></td>
                                            <td><span class="badge bg-secondary">#<?php echo htmlspecialchars($player['jersey_number']); ?></span></td>
                                            <td><?php echo $player['date_of_birth'] ? date_diff(date_create($player['date_of_birth']), date_create('today'))->y : 'N/A'; ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($player['gender'] ?? 'N/A')); ?></td>
                                            <td><span class="badge bg-<?php echo $player['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $player['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
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
            const input = document.getElementById('playersSearch');
            const table = document.getElementById('playersTable');
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
