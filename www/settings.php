<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('settings.view');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/supabase.php';

$pageTitle = 'Settings';
$currentPage = 'settings';
$settings = [];
$errorMessage = null;

try { $settings = get_settings(); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">SYSTEM CONFIGURATION</div><h1>Settings</h1><p class="page-subtitle">Runtime settings stored in Supabase for the dashboard and Solis services.</p></div>
</div>
<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">GENERAL</div><h2>System settings</h2></div></div>
    <div class="panel-body">
        <?php if (admin_has_permission('settings.manage')): ?>
        <form class="form-stack" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="settings_save">
            <input type="hidden" name="return_to" value="settings.php">
            <label><span>Site name</span><input type="text" name="site_name" value="<?= e($settings['site_name'] ?? 'Solis Admin') ?>"></label>
            <label class="checkbox-row"><input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : '' ?>><span>Maintenance mode</span></label>
            <label class="checkbox-row"><input type="checkbox" name="registration_enabled" value="1" <?= ($settings['registration_enabled'] ?? 'true') === 'true' ? 'checked' : '' ?>><span>Registration enabled</span></label>
            <label><span>Minimum supported Solis version</span><input type="text" name="minimum_supported_version" value="<?= e($settings['minimum_supported_version'] ?? '') ?>" placeholder="5.9.3"></label>
            <button class="button primary" type="submit">Save settings</button>
        </form>
        <?php else: ?>
            <div class="detail-grid"><div><span class="detail-label">Site name</span><strong><?= e($settings['site_name'] ?? 'Solis Admin') ?></strong></div><div><span class="detail-label">Maintenance</span><?= badge_html(($settings['maintenance_mode'] ?? 'false') === 'true' ? 'active' : 'disabled') ?></div><div><span class="detail-label">Registration</span><?= badge_html(($settings['registration_enabled'] ?? 'true') === 'true' ? 'active' : 'disabled') ?></div><div><span class="detail-label">Minimum supported</span><strong><?= e($settings['minimum_supported_version'] ?: 'Not set') ?></strong></div></div>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">SERVER</div><h2>Environment</h2></div></div>
    <div class="panel-body detail-grid">
        <div><span class="detail-label">PHP</span><strong><?= e(PHP_VERSION) ?></strong></div>
        <div><span class="detail-label">Timezone</span><strong><?= e((require __DIR__ . '/../config.php')['timezone']) ?></strong></div>
        <div><span class="detail-label">Supabase</span><?= supabase_is_configured() ? badge_html('connected', 'active') : badge_html('not configured', 'disabled') ?></div>
        <div><span class="detail-label">Web root</span><strong>/www</strong></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
