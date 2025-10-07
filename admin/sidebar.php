<?php
// Determine the active page
$current_page = basename($_SERVER['PHP_SELF']);

$nav_links = [
    'dashboard.php' => ['icon' => 'fa-tachometer-alt', 'text' => 'Dashboard'],
    'teams.php' => ['icon' => 'fa-users', 'text' => 'Teams'],
    'players.php' => ['icon' => 'fa-user', 'text' => 'Players'],
    'coaches.php' => ['icon' => 'fa-chalkboard-teacher', 'text' => 'Coaches'],
    'pending_coaches.php' => ['icon' => 'fa-user-clock', 'text' => 'Pending Coaches'],
    'manual_credential_sender.php' => ['icon' => 'fa-paper-plane', 'text' => 'Manual Send'],
    'reports.php' => ['icon' => 'fa-chart-bar', 'text' => 'Reports'],
    'settings.php' => ['icon' => 'fa-cog', 'text' => 'Settings'],
];

?>
<div class="sidebar p-3">
    <div class="text-center mb-4">
        <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo" style="width: 120px; height: auto;" class="mb-2">
        <h5 class="text-white mb-0"><?php echo APP_NAME; ?></h5>
        <small class="text-white-50">Admin Dashboard</small>
    </div>
    
    <nav class="nav flex-column">
        <?php foreach ($nav_links as $url => $link): ?>
            <a class="nav-link <?php echo ($current_page === $url) ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/<?php echo $url; ?>">
                <i class="fas <?php echo $link['icon']; ?> me-2"></i><?php echo $link['text']; ?>
            </a>
        <?php endforeach; ?>
        <hr class="text-white-50">
        <a class="nav-link" href="<?php echo APP_URL; ?>/auth/logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </nav>
</div>
