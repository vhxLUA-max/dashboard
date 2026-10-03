<?php
$currentPage = $currentPage ?? '';
?>
<aside class="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-mark">S</span>
        <span>
            <strong>Solis</strong>
            <small>Admin</small>
        </span>
    </a>

    <nav class="nav">
        <div class="nav-heading">MANAGEMENT</div>

        <a class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="index.php">
            <span>Dashboard</span>
        </a>

        <a class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>" href="users.php">
            <span>Users</span>
        </a>

        <a class="nav-item <?= $currentPage === 'devices' ? 'active' : '' ?>" href="#">
            <span>Devices</span>
        </a>

        <a class="nav-item <?= $currentPage === 'permissions' ? 'active' : '' ?>" href="#">
            <span>Permissions</span>
        </a>

        <div class="nav-heading">SYSTEM</div>

        <a class="nav-item <?= $currentPage === 'logs' ? 'active' : '' ?>" href="#">
            <span>Activity logs</span>
        </a>

        <a class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>" href="#">
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="admin-card">
            <span class="status-dot"></span>
            <div>
                <strong>Admin</strong>
                <small>Control panel</small>
            </div>
        </div>
    </div>
</aside>
