<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Authenticate and authorize viewer
if (!is_logged_in() || !has_permission('view_all_coaches')) {
    // This line assumes app_base_url() is defined in the files above.
    redirect(app_base_url() . '/auth/login.php');
}

$db = db();

// Pagination and ordering (latest first)
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Total count for pagination
$total_row = $db->fetchRow("\n    SELECT COUNT(*) AS cnt\n    FROM users u\n    LEFT JOIN coaches c ON u.id = c.user_id\n    LEFT JOIN teams t ON c.team_id = t.id\n    WHERE u.role = 'coach' AND u.approval_status = 'approved'\n");
$total = (int)($total_row['cnt'] ?? 0);
$total_pages = max(1, (int)ceil($total / $per_page));

// Get paged coaches with their team and approval status, latest first
$coaches = $db->fetchAll("\n    SELECT \n        u.id AS user_id,\n        u.first_name, \n        u.last_name, \n        u.email, \n        u.phone, \n        u.id_number,\n        u.approval_status,\n        c.license_number,\n        c.license_type,\n        c.experience_years,\n        c.specialization,\n        c.certifications,\n        c.coach_image,\n        t.name AS team_name,\n        t.team_code,\n        t.team_photo\n    FROM users u\n    LEFT JOIN coaches c ON u.id = c.user_id\n    LEFT JOIN teams t ON c.team_id = t.id\n    WHERE u.role = 'coach' AND u.approval_status = 'approved'\n    ORDER BY u.id DESC\n    LIMIT ? OFFSET ?\n", [$per_page, $offset]);

$page_title = 'View Coaches';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include dirname(__DIR__) . '/includes/head.php'; ?>
    <style>
        /* Simple styling for the viewer dashboard content area */
        .content-area {
            flex-grow: 1; /* Allows content area to take up remaining space */
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div class="content-area">
            <div class="container-fluid">
                <h2 class="mt-4"><i class="fas fa-chalkboard-teacher me-2"></i>All Coaches <span class="badge bg-primary ms-2">Total: <?php echo number_format($total); ?></span></h2>
                <hr>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="coachSearch" class="form-control" placeholder="Search coaches... (name, email, phone, team, status)">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="coachesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Coach</th>
                                        <th>Coach Photo</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>ID Number</th>
                                        <th>License</th>
                                        <th>Experience</th>
                                        <th>Specialization</th>
                                        <th>Certifications</th>
                                        <th>Team</th>
                                        <th>Team Photo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coaches)): ?>
                                        <tr>
                                            <td colspan="13" class="text-center text-muted py-4">No coach records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($coaches as $index => $coach): ?>
                                            <tr>
                                                <td><?php echo $offset + $index + 1; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if (!empty($coach['coach_image'])): ?>
                                                        <a href="../<?php echo htmlspecialchars($coach['coach_image']); ?>?v=<?php echo time(); ?>" target="_blank">
                                                            <img src="../<?php echo htmlspecialchars($coach['coach_image']); ?>?v=<?php echo time(); ?>" alt="Coach Photo" style="width: 75px; height: 75px; object-fit: cover; border: 1px solid #dee2e6; border-radius: 4px;" />
                                                        </a>
                                                    <?php else: ?>
                                                        <span class=\"text-muted\">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($coach['email'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($coach['phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($coach['id_number'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($coach['license_number'] ?? 'N/A'); ?>
                                                    <?php if (!empty($coach['license_type'])): ?>
                                                        <br><small class=\"text-muted\"><?php echo htmlspecialchars(ucfirst($coach['license_type'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo isset($coach['experience_years']) ? htmlspecialchars($coach['experience_years'] . ' yrs') : 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars($coach['specialization'] ?? 'N/A'); ?></td>
                                                <td><small><?php echo nl2br(htmlspecialchars($coach['certifications'] ?? 'N/A')); ?></small></td>
                                                <td>
                                                    <?php echo htmlspecialchars($coach['team_name'] ?? 'N/A'); ?>
                                                    <?php if (!empty($coach['team_code'])): ?>
                                                        <br><small class=\"text-muted\">Code: <?php echo htmlspecialchars($coach['team_code']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($coach['team_photo'])): ?>
                                                        <a href="../<?php echo htmlspecialchars($coach['team_photo']); ?>?v=<?php echo time(); ?>" target="_blank">
                                                            <img src="../<?php echo htmlspecialchars($coach['team_photo']); ?>?v=<?php echo time(); ?>" alt="Team Photo" style="width: 90px; height: 60px; object-fit: cover; border: 1px solid #dee2e6; border-radius: 4px;" />
                                                        </a>
                                                    <?php else: ?>
                                                        <span class=\"text-muted\">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $status = ucfirst($coach['approval_status']);
                                                        $badge_class = 'bg-secondary';
                                                        if ($coach['approval_status'] === 'approved') {
                                                            $badge_class = 'bg-success';
                                                        } elseif ($coach['approval_status'] === 'pending') {
                                                            $badge_class = 'bg-warning text-dark';
                                                        } elseif ($coach['approval_status'] === 'rejected') {
                                                            $badge_class = 'bg-danger';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                </div>
                                <nav aria-label="Coaches pagination">
                                    <ul class="pagination mb-0">
                                        <?php 
                                        $prev_disabled = $page <= 1 ? ' disabled' : '';
                                        $next_disabled = $page >= $total_pages ? ' disabled' : '';
                                        $prev_page = max(1, $page - 1);
                                        $next_page = min($total_pages, $page + 1);
                                        ?>
                                        <li class="page-item<?php echo $prev_disabled; ?>">
                                            <a class="page-link" href="?page=<?php echo $prev_page; ?>" tabindex="-1">Previous</a>
                                        </li>
                                        <?php 
                                        // Render a compact window of pages
                                        $window = 2; // pages on each side
                                        $start = max(1, $page - $window);
                                        $end = min($total_pages, $page + $window);
                                        if ($start > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                        }
                                        for ($p = $start; $p <= $end; $p++) {
                                            $active = $p == $page ? ' active' : '';
                                            echo '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $p . '">' . $p . '</a></li>';
                                        }
                                        if ($end < $total_pages) {
                                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>
                                        <li class="page-item<?php echo $next_disabled; ?>">
                                            <a class="page-link" href="?page=<?php echo $next_page; ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            const input = document.getElementById('coachSearch');
            const table = document.getElementById('coachesTable');
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