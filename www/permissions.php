<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('permissions.view');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/supabase.php';

$pageTitle = 'Permissions';
$currentPage = 'permissions';
$admins = [];
$errorMessage = null;

try { $admins = get_admin_users(); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }

$editing = null;
$editId = query_string('edit');
if ($editId !== '') {
    foreach ($admins as $item) {
        if ((string) $item['id'] === $editId) {
            $editing = $item;
            break;
        }
    }
}

$roles = [
    'owner' => 'Full access',
    'administrator' => 'Management access',
    'support' => 'Support access',
    'viewer' => 'Read-only access',
];

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">ACCESS CONTROL</div><h1>Permissions</h1><p class="page-subtitle">Manage dashboard operators and their roles.</p></div>
</div>
<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<div class="grid-2">
<?php if (admin_has_permission('permissions.manage')): ?>
<section class="panel">
    <div class="panel-header"><div><div class="eyebrow"><?= $editing ? 'EDIT ADMIN' : 'NEW ADMIN' ?></div><h2><?= $editing ? e($editing['username']) : 'Add admin' ?></h2></div><?php if ($editing): ?><a class="button" href="permissions.php">New</a><?php endif; ?></div>
    <div class="panel-body">
        <form class="form-stack" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_save">
            <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
            <input type="hidden" name="return_to" value="permissions.php">
            <label><span>Username</span><input type="text" name="username" value="<?= e($editing['username'] ?? '') ?>" required></label>
            <label><span>Role</span><select name="role"><?php foreach ($roles as $key => $label): ?><option value="<?= e($key) ?>" <?= ($editing['role'] ?? 'viewer') === $key ? 'selected' : '' ?>><?= e(ucfirst($key)) ?> · <?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span><?= $editing ? 'New password (optional)' : 'Password' ?></span><input type="password" name="password" minlength="8" <?= $editing ? '' : 'required' ?>></label>
            <label class="checkbox-row"><input type="checkbox" name="enabled" value="1" <?= (!$editing || (bool) ($editing['enabled'] ?? false)) ? 'checked' : '' ?>><span>Account enabled</span></label>
            <button class="button primary" type="submit"><?= $editing ? 'Save admin' : 'Create admin' ?></button>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">OPERATORS</div><h2>Admin accounts</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody>
    <?php foreach ($admins as $adminItem): ?>
        <tr>
            <td><strong><?= e($adminItem['username']) ?></strong></td>
            <td><?= e(ucfirst($adminItem['role'])) ?></td>
            <td><?= badge_html($adminItem['enabled'] ? 'active' : 'disabled', $adminItem['enabled'] ? 'active' : 'disabled') ?></td>
            <td><?= e(format_date($adminItem['created_at'])) ?></td>
            <td class="actions"><?php if (admin_has_permission('permissions.manage')): ?><a class="button small" href="permissions.php?edit=<?= e($adminItem['id']) ?>">Edit</a><?php if ((string) $adminItem['id'] !== (string) (current_admin()['id'] ?? '')): ?><form method="post" action="actions.php" onsubmit="return confirm('Delete this admin?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_delete"><input type="hidden" name="id" value="<?= e($adminItem['id']) ?>"><input type="hidden" name="return_to" value="permissions.php"><button class="button small danger" type="submit">Delete</button></form><?php endif; ?><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($admins === []): ?><tr><td colspan="5"><div class="empty-table">No database admins yet. The bootstrap owner is configured through environment variables.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
</div>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">ROLE MATRIX</div><h2>Access levels</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Area</th><th>Owner</th><th>Administrator</th><th>Support</th><th>Viewer</th></tr></thead><tbody>
    <?php foreach (permission_catalog() as $area => $permissionList): ?>
        <tr><td><strong><?= e($area) ?></strong></td><?php foreach (['owner','administrator','support','viewer'] as $role): ?><td><?= $role === 'owner' || (in_array($permissionList[0], role_permissions()[$role] ?? [], true) || in_array('*', role_permissions()[$role] ?? [], true)) ? 'View' : '—' ?><?php if (count($permissionList) > 1 && ($role === 'owner' || in_array($permissionList[1], role_permissions()[$role] ?? [], true))): ?> / Manage<?php endif; ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
