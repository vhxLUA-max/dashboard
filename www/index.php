<?php
declare(strict_types=1);

require __DIR__ . '/includes/supabase.php';

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

$accounts = [];
$errorMessage = null;

if (supabase_is_configured()) {
    try {
        $accounts = get_secondary_accounts();
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
} else {
    $errorMessage = 'Set SUPABASE_SECRET_KEY in the hosting environment.';
}

$totalUsers = count($accounts);
$activeUsers = count(array_filter($accounts, static fn (array $account): bool => (bool) $account['enabled']));
$disabledUsers = $totalUsers - $activeUsers;

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
        <?= $errorMessage === null ? 'Supabase connected' : 'Supabase unavailable' ?>
    </div>
</div>

<?php if ($errorMessage !== null): ?>
    <div class="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-label">Total users</div>
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-note">Secondary Solis accounts</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Active users</div>
        <div class="stat-value"><?= $activeUsers ?></div>
        <div class="stat-note">Accounts enabled</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Disabled users</div>
        <div class="stat-value"><?= $disabledUsers ?></div>
        <div class="stat-note">Accounts disabled</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Registered devices</div>
        <div class="stat-value">—</div>
        <div class="stat-note">No device table yet</div>
    </article>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <div class="eyebrow">ACCOUNT MANAGEMENT</div>
            <h2>Users</h2>
        </div>
        <a class="button" href="users.php">View users</a>
    </div>

    <?php if ($totalUsers > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Discord ID</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($accounts, 0, 10) as $account): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $account['username'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $account['discord_user_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $account['enabled'] ? 'Active' : 'Disabled' ?></td>
                            <td><?= htmlspecialchars((string) $account['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-mark">S</div>
            <h3>No Solis accounts found</h3>
            <p>Once accounts exist in public.secondary_accounts, they will appear here.</p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
