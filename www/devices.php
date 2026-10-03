<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('devices.view');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/supabase.php';

$pageTitle = 'Devices';
$currentPage = 'devices';
$devices = [];
$accounts = [];
$editing = null;
$errorMessage = null;

try { $devices = get_devices(); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }
try { $accounts = get_secondary_accounts(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }

$editId = query_string('edit');
if ($editId !== '') {
    try { $editing = get_device($editId); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
}

$active = count(array_filter($devices, static fn (array $device): bool => empty($device['revoked_at'])));
$revoked = count($devices) - $active;

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">HARDWARE</div><h1>Devices</h1><p class="page-subtitle">Track registered Solis installations and revoke access.</p></div>
</div>
<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<section class="stats-grid">
    <article class="stat-card"><div class="stat-label">Registered</div><div class="stat-value"><?= count($devices) ?></div><div class="stat-note">Known devices</div></article>
    <article class="stat-card"><div class="stat-label">Active</div><div class="stat-value"><?= $active ?></div><div class="stat-note">Not revoked</div></article>
    <article class="stat-card"><div class="stat-label">Revoked</div><div class="stat-value"><?= $revoked ?></div><div class="stat-note">Access disabled</div></article>
    <article class="stat-card"><div class="stat-label">Recently seen</div><div class="stat-value"><?= count(array_filter($devices, static fn (array $device): bool => !empty($device['last_seen_at']) && strtotime((string) $device['last_seen_at']) >= strtotime('-7 days'))) ?></div><div class="stat-note">Seen within 7 days</div></article>
</section>

<div class="grid-2">
<?php if (admin_has_permission('devices.manage')): ?>
<section class="panel">
    <div class="panel-header"><div><div class="eyebrow"><?= $editing ? 'EDIT DEVICE' : 'REGISTER DEVICE' ?></div><h2><?= $editing ? e($editing['device_name'] ?: $editing['device_uid']) : 'Add device' ?></h2></div><?php if ($editing): ?><a class="button" href="devices.php">New</a><?php endif; ?></div>
    <div class="panel-body">
        <form class="form-stack" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="device_save">
            <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
            <input type="hidden" name="return_to" value="devices.php">
            <label><span>User</span><select name="account_id" required><option value="">Select user</option><?php foreach ($accounts as $account): ?><option value="<?= e($account['id']) ?>" <?= (string) ($editing['account_id'] ?? '') === (string) $account['id'] ? 'selected' : '' ?>><?= e($account['username']) ?></option><?php endforeach; ?></select></label>
            <label><span>Device ID</span><input type="text" name="device_uid" value="<?= e($editing['device_uid'] ?? '') ?>" required></label>
            <label><span>Device name</span><input type="text" name="device_name" value="<?= e($editing['device_name'] ?? '') ?>" placeholder="Home PC"></label>
            <div class="form-grid"><label><span>Solis version</span><input type="text" name="app_version" value="<?= e($editing['app_version'] ?? '') ?>" placeholder="5.9.3"></label><label><span>Windows version</span><input type="text" name="os_version" value="<?= e($editing['os_version'] ?? '') ?>" placeholder="Windows 11"></label></div>
            <label><span>Last seen</span><input type="datetime-local" name="last_seen_at" value="<?= e(datetime_local_value($editing['last_seen_at'] ?? null)) ?>"></label>
            <label><span>Notes</span><textarea name="notes" rows="3"><?= e($editing['notes'] ?? '') ?></textarea></label>
            <button class="button primary" type="submit"><?= $editing ? 'Save device' : 'Add device' ?></button>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">REGISTRY</div><h2>Devices</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>User</th><th>Device</th><th>Solis</th><th>OS</th><th>Last seen</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($devices as $device): ?>
        <?php $status = !empty($device['revoked_at']) ? 'revoked' : 'active'; ?>
        <tr>
            <td><?= e($device['account']['username'] ?? 'Unknown') ?></td>
            <td><strong><?= e($device['device_name'] ?: $device['device_uid']) ?></strong><div class="muted tiny"><?= e($device['device_uid']) ?></div></td>
            <td><?= e($device['app_version'] ?: '—') ?></td>
            <td><?= e($device['os_version'] ?: '—') ?></td>
            <td><?= e(format_date($device['last_seen_at'])) ?></td>
            <td><?= badge_html($status) ?></td>
            <td class="actions"><?php if (admin_has_permission('devices.manage')): ?><a class="button small" href="devices.php?edit=<?= e($device['id']) ?>">Edit</a><?php if ($status === 'active'): ?><form method="post" action="actions.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="device_revoke"><input type="hidden" name="id" value="<?= e($device['id']) ?>"><input type="hidden" name="return_to" value="devices.php"><button class="button small" type="submit">Revoke</button></form><?php else: ?><form method="post" action="actions.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="device_restore"><input type="hidden" name="id" value="<?= e($device['id']) ?>"><input type="hidden" name="return_to" value="devices.php"><button class="button small" type="submit">Restore</button></form><?php endif; ?><form method="post" action="actions.php" onsubmit="return confirm('Delete this device?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="device_delete"><input type="hidden" name="id" value="<?= e($device['id']) ?>"><input type="hidden" name="return_to" value="devices.php"><button class="button small danger" type="submit">Delete</button></form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($devices === []): ?><tr><td colspan="7"><div class="empty-table">No devices registered yet.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
