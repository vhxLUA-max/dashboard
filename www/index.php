<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('dashboard.view');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

$accounts = [];
$subscriptions = [];
$devices = [];
$logs = [];
$releases = [];
$errorMessage = null;

if (!supabase_is_configured()) {
    $errorMessage = 'Set SUPABASE_SECRET_KEY in the hosting environment.';
} else {
    try { $accounts = get_secondary_accounts(); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }
    try { $subscriptions = get_subscriptions(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
    try { $devices = get_devices(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
    try { $logs = get_activity_logs(12); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
    try { sync_github_releases(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
    try { $releases = get_releases(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
}

$now = new DateTimeImmutable('now');
$sevenDays = $now->modify('+7 days');
$totalUsers = count($accounts);
$activeUsers = count(array_filter($accounts, static fn (array $account): bool => (bool) $account['enabled']));
$disabledUsers = $totalUsers - $activeUsers;
$activeSubscriptions = count(array_filter($subscriptions, static fn (array $item): bool => in_array((string) ($item['status'] ?? ''), ['active', 'trialing'], true)));
$expiringSoon = count(array_filter($subscriptions, static function (array $item) use ($now, $sevenDays): bool {
    if (empty($item['expires_at']) || !in_array((string) ($item['status'] ?? ''), ['active', 'trialing'], true)) return false;
    try {
        $expiry = new DateTimeImmutable((string) $item['expires_at']);
        return $expiry >= $now && $expiry <= $sevenDays;
    } catch (Throwable) {
        return false;
    }
}));
$activeDevices = count(array_filter($devices, static fn (array $device): bool => empty($device['revoked_at'])));
$publishedRelease = null;
foreach ($releases as $release) {
    if ((string) ($release['status'] ?? '') === 'published') {
        $publishedRelease = $release;
        break;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">SOLIS CONTROL</div><h1>Dashboard</h1><p class="page-subtitle">Accounts, subscriptions, devices, releases, and system activity.</p></div>
    <div class="system-status"><span class="status-dot"></span><?= $errorMessage === null ? 'Supabase connected' : 'Action required' ?></div>
</div>

<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<section class="stats-grid">
    <article class="stat-card"><div class="stat-label">Total users</div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-note">Secondary Solis accounts</div></article>
    <article class="stat-card"><div class="stat-label">Active users</div><div class="stat-value"><?= $activeUsers ?></div><div class="stat-note"><?= $disabledUsers ?> disabled</div></article>
    <article class="stat-card"><div class="stat-label">Active subscriptions</div><div class="stat-value"><?= $activeSubscriptions ?></div><div class="stat-note"><?= $expiringSoon ?> expiring within 7 days</div></article>
    <article class="stat-card"><div class="stat-label">Active devices</div><div class="stat-value"><?= $activeDevices ?></div><div class="stat-note"><?= count($devices) ?> registered</div></article>
</section>

<div class="grid-2">
    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">ACCOUNTS</div><h2>Recent users</h2></div><a class="button" href="users.php">Manage users</a></div>
        <div class="table-wrap"><table><thead><tr><th>Username</th><th>Status</th><th>Created</th></tr></thead><tbody>
        <?php foreach (array_slice($accounts, 0, 8) as $account): ?>
            <tr><td><a class="table-link" href="user.php?id=<?= e($account['id']) ?>"><?= e($account['username']) ?></a></td><td><?= badge_html($account['enabled'] ? 'active' : 'disabled', $account['enabled'] ? 'active' : 'disabled') ?></td><td><?= e(format_date($account['created_at'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($accounts === []): ?><tr><td colspan="3"><div class="empty-table">No users to display.</div></td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">SYSTEM</div><h2>Latest release</h2></div><a class="button" href="releases.php">View releases</a></div>
        <div class="panel-body">
            <?php if ($publishedRelease): ?>
                <div class="release-highlight"><div><span class="eyebrow">PUBLISHED</span><strong><?= e($publishedRelease['version']) ?></strong><span><?= e(ucfirst((string) $publishedRelease['channel'])) ?></span></div><?php if (!empty($publishedRelease['download_url'])): ?><a class="button" href="<?= e($publishedRelease['download_url']) ?>" target="_blank" rel="noreferrer">Open release</a><?php endif; ?></div>
                <?php if (!empty($publishedRelease['notes'])): ?><p class="muted"><?= e($publishedRelease['notes']) ?></p><?php endif; ?>
            <?php else: ?>
                <div class="empty-state compact"><div class="empty-mark">S</div><h3>No published release</h3><p>Add a release from the Releases page.</p></div>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">AUDIT</div><h2>Recent activity</h2></div><a class="button" href="logs.php">View logs</a></div>
    <div class="table-wrap"><table><thead><tr><th>Action</th><th>Actor</th><th>Target</th><th>Time</th></tr></thead><tbody>
    <?php foreach ($logs as $log): ?>
        <tr><td><code><?= e($log['action']) ?></code></td><td><?= e($log['actor_name'] ?? 'System') ?></td><td><?= e(($log['entity_type'] ?? '') . (isset($log['entity_id']) && $log['entity_id'] !== null ? ' · ' . $log['entity_id'] : '')) ?></td><td><?= e(format_date($log['created_at'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if ($logs === []): ?><tr><td colspan="4"><div class="empty-table">No activity yet.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
