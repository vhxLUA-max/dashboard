<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('devices.view');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

$pageTitle = 'Devices';
$currentPage = 'devices';
$devices = [];
$errorMessage = null;

try {
    $devices = get_devices();
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

$now = new DateTimeImmutable('now');
$onlineCutoff = $now->modify('-2 minutes');

$online = 0;
$offline = 0;
$revoked = 0;

foreach ($devices as $device) {
    if (!empty($device['revoked_at'])) {
        $revoked++;
        continue;
    }

    try {
        $lastSeen = !empty($device['last_seen_at'])
            ? new DateTimeImmutable((string) $device['last_seen_at'])
            : null;
    } catch (Throwable) {
        $lastSeen = null;
    }

    if ($lastSeen && $lastSeen >= $onlineCutoff) {
        $online++;
    } else {
        $offline++;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="eyebrow">HARDWARE</div>
        <h1>Devices</h1>
        <p class="page-subtitle">Automatically detected Solis installations. Device records are created and refreshed by Solis.</p>
    </div>
</div>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-warning"><?= e($errorMessage) ?></div>
<?php endif; ?>

<section class="stats-grid">
    <article class="stat-card"><div class="stat-label">Registered</div><div class="stat-value"><?= count($devices) ?></div><div class="stat-note">Detected installations</div></article>
    <article class="stat-card"><div class="stat-label">Online</div><div class="stat-value"><?= $online ?></div><div class="stat-note">Seen within 2 minutes</div></article>
    <article class="stat-card"><div class="stat-label">Offline</div><div class="stat-value"><?= $offline ?></div><div class="stat-note">No heartbeat within 2 minutes</div></article>
    <article class="stat-card"><div class="stat-label">Revoked</div><div class="stat-value"><?= $revoked ?></div><div class="stat-note">Blocked installations</div></article>
</section>

<section class="panel">
    <div class="panel-header">
        <div><div class="eyebrow">AUTOMATIC REGISTRY</div><h2>Detected devices</h2></div>
        <div class="toolbar-note">Solis heartbeat: every 30 seconds</div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Device ID</th>
                    <th>Solis Version</th>
                    <th>Windows Version</th>
                    <th>Last Seen</th>
                    <th>First Seen</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Last IP</th>
                    <?php if (admin_has_permission('devices.manage')): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($devices as $device): ?>
                <?php
                $isRevoked = !empty($device['revoked_at']);
                try {
                    $lastSeen = !empty($device['last_seen_at']) ? new DateTimeImmutable((string) $device['last_seen_at']) : null;
                } catch (Throwable) {
                    $lastSeen = null;
                }
                $status = $isRevoked ? 'revoked' : (($lastSeen && $lastSeen >= $onlineCutoff) ? 'online' : 'offline');
                ?>
                <tr>
                    <td><code><?= e($device['device_uid']) ?></code></td>
                    <td><?= e($device['app_version'] ?: '—') ?></td>
                    <td><?= e($device['os_version'] ?: '—') ?></td>
                    <td><?= e(format_date($device['last_seen_at'])) ?></td>
                    <td><?= e(format_date($device['first_seen_at'])) ?></td>
                    <td>
                        <?php if (!empty($device['account']['id'])): ?>
                            <a class="table-link" href="user.php?id=<?= e($device['account']['id']) ?>"><?= e($device['account']['username'] ?? 'Unknown') ?></a>
                        <?php else: ?>
                            Unknown
                        <?php endif; ?>
                    </td>
                    <td><?= badge_html($status) ?></td>
                    <td><?= e($device['last_ip'] ?? '—') ?></td>
                    <?php if (admin_has_permission('devices.manage')): ?>
                    <td class="actions">
                        <?php if (!$isRevoked): ?>
                            <form method="post" action="actions.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="device_revoke">
                                <input type="hidden" name="id" value="<?= e($device['id']) ?>">
                                <input type="hidden" name="return_to" value="devices.php">
                                <button class="button small" type="submit">Revoke</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="actions.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="device_restore">
                                <input type="hidden" name="id" value="<?= e($device['id']) ?>">
                                <input type="hidden" name="return_to" value="devices.php">
                                <button class="button small" type="submit">Restore</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="actions.php" onsubmit="return confirm('Remove this device record? Solis will register it again on the next heartbeat unless it is revoked.');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="device_delete">
                            <input type="hidden" name="id" value="<?= e($device['id']) ?>">
                            <input type="hidden" name="return_to" value="devices.php">
                            <button class="button small danger" type="submit">Remove</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>

            <?php if ($devices === []): ?>
                <tr><td colspan="<?= admin_has_permission('devices.manage') ? 9 : 8 ?>"><div class="empty-table">No devices have checked in yet. Open Solis while signed in and the installation will appear automatically.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">DATA SOURCE</div><h2>How device detection works</h2></div></div>
    <div class="panel-body">
        <p class="muted">Solis generates a stable installation ID under <code>%LocalAppData%\Solis\device-id.txt</code>. After authentication, the desktop app sends the ID, current Solis version, and Windows version to the protected <code>device-heartbeat</code> function every 30 seconds. The server resolves the authenticated Solis account and records the request's observed IP address.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
