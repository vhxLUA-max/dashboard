<?php
declare(strict_types=1);

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="eyebrow">SOLIS CONTROL</div>
        <h1>Dashboard</h1>
        <p class="page-subtitle">Manage Solis accounts and system access.</p>
    </div>
    <div class="system-status">
        <span class="status-dot"></span>
        System online
    </div>
</div>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-label">Total users</div>
        <div class="stat-value">—</div>
        <div class="stat-note">Waiting for Supabase</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Active users</div>
        <div class="stat-value">—</div>
        <div class="stat-note">Waiting for Supabase</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Disabled users</div>
        <div class="stat-value">—</div>
        <div class="stat-note">Waiting for Supabase</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Registered devices</div>
        <div class="stat-value">—</div>
        <div class="stat-note">Waiting for Supabase</div>
    </article>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <div class="eyebrow">ACCOUNT MANAGEMENT</div>
            <h2>Users</h2>
        </div>
        <a class="button disabled" href="#">Add user</a>
    </div>

    <div class="empty-state">
        <div class="empty-mark">S</div>
        <h3>User management is not connected</h3>
        <p>The dashboard is ready. Database and authentication integration will be connected after the Solis account structure is finalized.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
