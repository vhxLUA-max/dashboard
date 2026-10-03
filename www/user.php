<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('users.view');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

$id = query_string('id');
$account = $id !== '' ? get_secondary_account($id) : null;

if (!$account) {
    redirect_to('users.php');
}

$pageTitle = 'User details';
$currentPage = 'users';
$provision = null;
$subscriptions = [];
$devices = [];

try { $provision = get_provision_request((string) $account['discord_user_id']); } catch (Throwable) {}
try { $subscriptions = array_values(array_filter(get_subscriptions(), static fn (array $item): bool => (string) ($item['account_id'] ?? '') === $id)); } catch (Throwable) {}
try { $devices = array_values(array_filter(get_devices(), static fn (array $item): bool => (string) ($item['account_id'] ?? '') === $id)); } catch (Throwable) {}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">USER PROFILE</div><h1><?= e($account['username']) ?></h1><p class="page-subtitle">Discord <?= e($account['discord_user_id']) ?></p></div>
    <div class="button-row"><a class="button" href="users.php">Back to users</a><?php if (admin_has_permission('subscriptions.manage')): ?><a class="button primary" href="subscriptions.php?account_id=<?= e($account['id']) ?>">Manage subscription</a><?php endif; ?></div>
</div>

<div class="grid-2">
    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">ACCOUNT</div><h2>Profile</h2></div></div>
        <div class="panel-body detail-grid">
            <div><span class="detail-label">Username</span><strong><?= e($account['username']) ?></strong></div>
            <div><span class="detail-label">Discord ID</span><strong><?= e($account['discord_user_id']) ?></strong></div>
            <div><span class="detail-label">Status</span><?= badge_html($account['enabled'] ? 'active' : 'disabled', $account['enabled'] ? 'active' : 'disabled') ?></div>
            <div><span class="detail-label">Created</span><strong><?= e(format_date($account['created_at'])) ?></strong></div>
            <div><span class="detail-label">Updated</span><strong><?= e(format_date($account['updated_at'])) ?></strong></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">CREDENTIALS</div><h2>Provisioning</h2></div></div>
        <div class="panel-body">
            <?php if ($provision): ?>
                <div class="inline-meta"><span>Status</span><?= badge_html($provision['status']) ?></div>
                <div class="inline-meta"><span>Attempts</span><strong><?= e($provision['attempts']) ?></strong></div>
                <div class="inline-meta"><span>Updated</span><strong><?= e(format_date($provision['updated_at'])) ?></strong></div>
                <?php if (!empty($provision['error_message'])): ?><div class="alert alert-danger compact-alert"><?= e($provision['error_message']) ?></div><?php endif; ?>
            <?php else: ?>
                <p class="muted">No provisioning request exists yet.</p>
            <?php endif; ?>

            <?php if (admin_has_permission('users.manage')): ?>
                <form method="post" action="actions.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="user_provision">
                    <input type="hidden" name="id" value="<?= e($account['id']) ?>">
                    <input type="hidden" name="return_to" value="<?= e('user.php?id=' . $account['id']) ?>">
                    <button class="button primary button-block" type="submit">Regenerate credentials</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if (admin_has_permission('users.manage')): ?>
<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">EDIT</div><h2>Account settings</h2></div></div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="user_update">
            <input type="hidden" name="id" value="<?= e($account['id']) ?>">
            <input type="hidden" name="return_to" value="<?= e('user.php?id=' . $account['id']) ?>">
            <label><span>Username</span><input type="text" name="username" value="<?= e($account['username']) ?>" required></label>
            <label class="checkbox-row"><input type="checkbox" name="enabled" value="1" <?= $account['enabled'] ? 'checked' : '' ?>><span>Account enabled</span></label>
            <div class="form-actions"><button class="button primary" type="submit">Save changes</button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<div class="grid-2">
    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">SUBSCRIPTIONS</div><h2>Subscriptions</h2></div></div>
        <div class="table-wrap">
            <table><thead><tr><th>Plan</th><th>Status</th><th>Expires</th></tr></thead><tbody>
            <?php foreach ($subscriptions as $subscription): ?>
                <tr><td><a class="table-link" href="subscription.php?id=<?= e($subscription['id']) ?>"><?= e($subscription['plan']['name'] ?? 'Unassigned') ?></a></td><td><?= badge_html($subscription['status']) ?></td><td><?= e(format_date($subscription['expires_at'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($subscriptions === []): ?><tr><td colspan="3"><div class="empty-table">No subscriptions.</div></td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">DEVICES</div><h2>Registered devices</h2></div></div>
        <div class="table-wrap">
            <table><thead><tr><th>Device</th><th>Version</th><th>Last seen</th></tr></thead><tbody>
            <?php foreach ($devices as $device): ?>
                <tr><td><?= e($device['device_name'] ?: $device['device_uid']) ?></td><td><?= e($device['app_version'] ?: '—') ?></td><td><?= e(format_date($device['last_seen_at'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($devices === []): ?><tr><td colspan="3"><div class="empty-table">No devices.</div></td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</div>

<?php if (admin_has_permission('users.manage')): ?>
<section class="danger-zone panel">
    <div class="panel-header"><div><div class="eyebrow">DANGER ZONE</div><h2>Delete account</h2></div></div>
    <div class="panel-body split">
        <p class="muted">Deleting this Solis account also removes linked subscriptions and devices.</p>
        <form method="post" action="actions.php" onsubmit="return confirm('Delete this account? This cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="user_delete">
            <input type="hidden" name="id" value="<?= e($account['id']) ?>">
            <input type="hidden" name="return_to" value="users.php">
            <button class="button danger" type="submit">Delete account</button>
        </form>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
