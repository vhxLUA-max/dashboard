<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('releases.view');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

$pageTitle = 'Releases';
$currentPage = 'releases';
$releases = [];
$errorMessage = null;

try { $releases = get_releases(); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }

$editing = null;
$editId = query_string('edit');
if ($editId !== '') {
    foreach ($releases as $release) {
        if ((string) $release['id'] === $editId) {
            $editing = $release;
            break;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">RELEASE MANAGEMENT</div><h1>Releases</h1><p class="page-subtitle">Track published Solis versions and minimum supported versions.</p></div>
</div>
<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<div class="grid-2">
<?php if (admin_has_permission('releases.manage')): ?>
<section class="panel">
    <div class="panel-header"><div><div class="eyebrow"><?= $editing ? 'EDIT RELEASE' : 'NEW RELEASE' ?></div><h2><?= $editing ? e($editing['version']) : 'Add release' ?></h2></div><?php if ($editing): ?><a class="button" href="releases.php">New</a><?php endif; ?></div>
    <div class="panel-body">
        <form class="form-stack" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="release_save">
            <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
            <input type="hidden" name="return_to" value="releases.php">
            <label><span>Version</span><input type="text" name="version" value="<?= e($editing['version'] ?? '') ?>" placeholder="5.9.3" required></label>
            <div class="form-grid"><label><span>Channel</span><select name="channel"><?php foreach (['stable','beta','nightly'] as $value): ?><option value="<?= e($value) ?>" <?= ($editing['channel'] ?? 'stable') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label><label><span>Status</span><select name="status"><?php foreach (['draft','published','retired'] as $value): ?><option value="<?= e($value) ?>" <?= ($editing['status'] ?? 'draft') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label></div>
            <div class="form-grid"><label><span>Minimum supported</span><input type="text" name="min_supported_version" value="<?= e($editing['min_supported_version'] ?? '') ?>" placeholder="5.8.0"></label><label><span>Release date</span><input type="datetime-local" name="release_date" value="<?= e(datetime_local_value($editing['release_date'] ?? null)) ?>"></label></div>
            <label><span>Download URL</span><input type="url" name="download_url" value="<?= e($editing['download_url'] ?? '') ?>" placeholder="https://github.com/..."></label>
            <label><span>Notes</span><textarea name="notes" rows="4"><?= e($editing['notes'] ?? '') ?></textarea></label>
            <button class="button primary" type="submit"><?= $editing ? 'Save release' : 'Add release' ?></button>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">CHANNELS</div><h2>Release history</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Version</th><th>Channel</th><th>Status</th><th>Minimum</th><th>Release date</th><th></th></tr></thead><tbody>
    <?php foreach ($releases as $release): ?>
        <tr>
            <td><strong><?= e($release['version']) ?></strong></td>
            <td><?= badge_html($release['channel']) ?></td>
            <td><?= badge_html($release['status']) ?></td>
            <td><?= e($release['min_supported_version'] ?: '—') ?></td>
            <td><?= e(format_date($release['release_date'])) ?></td>
            <td class="actions"><?php if (admin_has_permission('releases.manage')): ?><a class="button small" href="releases.php?edit=<?= e($release['id']) ?>">Edit</a><?php if (!empty($release['download_url'])): ?><a class="button small" href="<?= e($release['download_url']) ?>" target="_blank" rel="noreferrer">Open</a><?php endif; ?><form method="post" action="actions.php" onsubmit="return confirm('Delete this release?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="release_delete"><input type="hidden" name="id" value="<?= e($release['id']) ?>"><input type="hidden" name="return_to" value="releases.php"><button class="button small danger" type="submit">Delete</button></form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($releases === []): ?><tr><td colspan="6"><div class="empty-table">No releases recorded yet.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
