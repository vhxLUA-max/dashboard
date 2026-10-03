<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('users.view');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

$pageTitle = 'Users';
$currentPage = 'users';
$search = query_string('search');
$status = query_string('status', 'all');
$status = in_array($status, ['all', 'active', 'disabled'], true) ? $status : 'all';

$accounts = [];
$errorMessage = null;
try {
    if (supabase_is_configured()) {
        $accounts = get_secondary_accounts($search, $status);
    } else {
        $errorMessage = 'Set SUPABASE_SECRET_KEY in the hosting environment.';
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

$returnQuery = http_build_query(array_filter(['search' => $search, 'status' => $status !== 'all' ? $status : null], static fn ($value): bool => $value !== null && $value !== ''));
$returnTo = 'users.php' . ($returnQuery !== '' ? '?' . $returnQuery : '');

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">ACCOUNT MANAGEMENT</div><h1>Users</h1><p class="page-subtitle">Manage Solis secondary accounts and credential provisioning.</p></div>
</div>
<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-toolbar">
        <form class="toolbar-form" method="get">
            <div class="search-box"><input type="search" name="search" value="<?= e($search) ?>" placeholder="Search username or Discord ID"></div>
            <select name="status"><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All status</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option><option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Disabled</option></select>
            <button class="button" type="submit">Filter</button>
        </form>
        <div class="button-row"><?php if (admin_has_permission('subscriptions.manage')): ?><a class="button" href="subscriptions.php">Subscriptions</a><?php endif; ?><div class="toolbar-note"><?= count($accounts) ?> result<?= count($accounts) === 1 ? '' : 's' ?></div></div>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Username</th><th>Discord ID</th><th>Status</th><th>Created</th><th>Updated</th><th></th></tr></thead><tbody>
    <?php foreach ($accounts as $account): ?>
        <tr>
            <td><a class="table-link" href="user.php?id=<?= e($account['id']) ?>"><?= e($account['username']) ?></a></td>
            <td><?= e($account['discord_user_id']) ?></td>
            <td><?= badge_html($account['enabled'] ? 'active' : 'disabled', $account['enabled'] ? 'active' : 'disabled') ?></td>
            <td><?= e(format_date($account['created_at'])) ?></td>
            <td><?= e(format_date($account['updated_at'])) ?></td>
            <td class="actions"><?php if (admin_has_permission('users.manage')): ?><form method="post" action="actions.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="user_toggle"><input type="hidden" name="id" value="<?= e($account['id']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><button class="button small" type="submit"><?= $account['enabled'] ? 'Disable' : 'Enable' ?></button></form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($accounts === []): ?><tr><td colspan="6"><div class="empty-table">No users match the current filters.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
