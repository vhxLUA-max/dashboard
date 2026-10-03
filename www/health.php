<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('health.view');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/supabase.php';

$pageTitle = 'System health';
$currentPage = 'health';

$checks = [];
if (!supabase_is_configured()) {
    $checks[] = ['name' => 'Supabase configuration', 'status' => 'failed', 'detail' => 'SUPABASE_SECRET_KEY is missing.'];
} else {
    $checks[] = ['name' => 'Supabase configuration', 'status' => 'ok', 'detail' => 'Server-side secret key is configured.'];

    try {
        $accounts = get_secondary_accounts();
        $checks[] = ['name' => 'Supabase REST', 'status' => 'ok', 'detail' => 'REST API responded successfully.'];
    } catch (Throwable $e) {
        $checks[] = ['name' => 'Supabase REST', 'status' => 'failed', 'detail' => $e->getMessage()];
    }

    foreach ([
        ['label' => 'Subscription tables', 'table' => 'subscription_plans?select=id&limit=1'],
        ['label' => 'Device table', 'table' => 'devices?select=id&limit=1'],
        ['label' => 'Activity log', 'table' => 'activity_logs?select=id&limit=1'],
        ['label' => 'Release table', 'table' => 'app_releases?select=id&limit=1'],
        ['label' => 'Settings table', 'table' => 'system_settings?select=key&limit=1'],
        ['label' => 'Admin table', 'table' => 'admin_users?select=id&limit=1'],
    ] as $item) {
        try {
            supabase_request($item['table']);
            $checks[] = ['name' => $item['label'], 'status' => 'ok', 'detail' => 'Table is reachable server-side.'];
        } catch (Throwable $e) {
            $checks[] = ['name' => $item['label'], 'status' => 'failed', 'detail' => $e->getMessage()];
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">DIAGNOSTICS</div><h1>System health</h1><p class="page-subtitle">Basic checks for the PHP admin backend and Supabase integration.</p></div>
</div>

<section class="health-grid">
<?php foreach ($checks as $check): ?>
    <article class="health-card">
        <div class="health-top"><strong><?= e($check['name']) ?></strong><?= badge_html($check['status'] === 'ok' ? 'healthy' : 'failed', $check['status'] === 'ok' ? 'active' : 'failed') ?></div>
        <p><?= e($check['detail']) ?></p>
    </article>
<?php endforeach; ?>
</section>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">NOTES</div><h2>Runtime</h2></div></div>
    <div class="panel-body detail-grid">
        <div><span class="detail-label">PHP</span><strong><?= e(PHP_VERSION) ?></strong></div>
        <div><span class="detail-label">cURL</span><strong><?= function_exists('curl_init') ? 'Available' : 'Missing' ?></strong></div>
        <div><span class="detail-label">Sessions</span><strong><?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?></strong></div>
        <div><span class="detail-label">Admin role</span><strong><?= e(current_admin()['role'] ?? 'unknown') ?></strong></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
