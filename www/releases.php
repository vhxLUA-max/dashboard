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
$syncedCount = 0;

try {
    $synced = sync_github_releases();
    $syncedCount = count($synced);
    $releases = get_releases();
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();

    try {
        $releases = get_releases();
    } catch (Throwable $fallback) {
        $errorMessage = $fallback->getMessage();
    }
}

$config = require __DIR__ . '/../config.php';
$releaseRepository = $config['release_repository'];

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="eyebrow">RELEASE FEED</div>
        <h1>Releases</h1>
        <p class="page-subtitle">Release information is automatically synced from the Solis GitHub release repository.</p>
    </div>
    <a class="button" href="releases.php">Refresh</a>
</div>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-warning"><?= e($errorMessage) ?></div>
<?php else: ?>
    <div class="alert alert-success">
        Synced <?= $syncedCount ?> release<?= $syncedCount === 1 ? '' : 's' ?> from
        <code><?= e($releaseRepository) ?></code>.
    </div>
<?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div><div class="eyebrow">AUTOMATIC SOURCE</div><h2>Release history</h2></div>
        <a class="button" href="https://github.com/<?= e($releaseRepository) ?>/releases" target="_blank" rel="noreferrer">Open GitHub releases</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Version</th><th>Channel</th><th>Status</th><th>Release date</th><th>Notes</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($releases as $release): ?>
                <tr>
                    <td><strong><?= e($release['version']) ?></strong></td>
                    <td><?= badge_html($release['channel']) ?></td>
                    <td><?= badge_html($release['status']) ?></td>
                    <td><?= e(format_date($release['release_date'])) ?></td>
                    <td class="release-notes"><?= e($release['notes'] ?: '—') ?></td>
                    <td><?php if (!empty($release['download_url'])): ?><a class="button small" href="<?= e($release['download_url']) ?>" target="_blank" rel="noreferrer">Open</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if ($releases === []): ?>
                <tr><td colspan="6"><div class="empty-table">No published GitHub releases were found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">SOURCE</div><h2>Automatic behavior</h2></div></div>
    <div class="panel-body">
        <p class="muted">The dashboard reads published GitHub Releases, stores them as a local Supabase cache, and refreshes that cache whenever this page is opened. There are no manual release version, status, download URL, or notes fields.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
