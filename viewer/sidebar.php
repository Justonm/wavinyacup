<?php
// viewer/sidebar.php

if (!isset($user)) {
    $user = get_logged_in_user();
}
?>
<div class="sidebar" id="sidebar-wrapper">
    <div class="sidebar-header">
        <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo">
        <h5 class="sidebar-title"><?php echo APP_NAME; ?></h5>
        <p class="sidebar-subtitle">Wavinya Cup</p>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <?php if (has_permission('view_all_teams')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_teams.php' ? 'active' : ''; ?>" href="view_teams.php">
                <i class="fas fa-shield-alt"></i> View Teams
            </a>
        </li>
        <?php endif; ?>
        <?php if (has_permission('view_all_players')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_players.php' ? 'active' : ''; ?>" href="view_players.php">
                <i class="fas fa-users"></i> View Players
            </a>
        </li>
        <?php endif; ?>
        <?php if (has_permission('view_all_coaches')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_coaches.php' ? 'active' : ''; ?>" href="view_coaches.php">
                <i class="fas fa-chalkboard-teacher"></i> View Coaches
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
        <a href="<?php echo app_base_url(); ?>/auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
