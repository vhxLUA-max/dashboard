<?php
$currentPage = $currentPage ?? '';
$admin = current_admin();
?>
<aside class="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-mark">S</span>
        <span>
            <strong>Solis</strong>
            <small>Admin Control</small>
        </span>
    </a>

    <nav class="nav">
        <div class="nav-heading">MANAGEMENT</div>

        <?php if (admin_has_permission('dashboard.view')): ?>
            <a class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="index.php">Dashboard</a>
        <?php endif; ?>
        <?php if (admin_has_permission('users.view')): ?>
            <a class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>" href="users.php">Users</a>
        <?php endif; ?>
        <?php if (admin_has_permission('subscriptions.view')): ?>
            <a class="nav-item <?= $currentPage === 'subscriptions' ? 'active' : '' ?>" href="subscriptions.php">Subscriptions</a>
        <?php endif; ?>
        <?php if (admin_has_permission('devices.view')): ?>
            <a class="nav-item <?= $currentPage === 'devices' ? 'active' : '' ?>" href="devices.php">Devices</a>
        <?php endif; ?>

        <div class="nav-heading">SYSTEM</div>

        <?php if (admin_has_permission('logs.view')): ?>
            <a class="nav-item <?= $currentPage === 'logs' ? 'active' : '' ?>" href="logs.php">Activity logs</a>
        <?php endif; ?>
        <?php if (admin_has_permission('permissions.view')): ?>
            <a class="nav-item <?= $currentPage === 'permissions' ? 'active' : '' ?>" href="permissions.php">Permissions</a>
        <?php endif; ?>
        <?php if (admin_has_permission('releases.view')): ?>
            <a class="nav-item <?= $currentPage === 'releases' ? 'active' : '' ?>" href="releases.php">Releases</a>
        <?php endif; ?>
        <?php if (admin_has_permission('settings.view')): ?>
            <a class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>" href="settings.php">Settings</a>
        <?php endif; ?>
        <?php if (admin_has_permission('health.view')): ?>
            <a class="nav-item <?= $currentPage === 'health' ? 'active' : '' ?>" href="health.php">System health</a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="admin-card">
            <span class="status-dot"></span>
            <div class="admin-meta">
                <strong><?= e($admin['username'] ?? 'Admin') ?></strong>
                <small><?= e(ucfirst((string) ($admin['role'] ?? 'viewer'))) ?></small>
            </div>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="icon-button" type="submit" aria-label="Log out">×</button>
            </form>
        </div>
    </div>
</aside>
