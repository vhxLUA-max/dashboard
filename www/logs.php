<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('logs.view');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/supabase.php';

$pageTitle = 'Activity logs';
$currentPage = 'logs';
$logs = [];
$errorMessage = null;

try { $logs = get_activity_logs(300); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">AUDIT TRAIL</div><h1>Activity logs</h1><p class="page-subtitle">Administrative actions and important account events.</p></div>
</div>
<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">LATEST</div><h2><?= count($logs) ?> events</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead><tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= e(format_date($log['created_at'])) ?></td>
            <td><?= e($log['actor_name'] ?: 'System') ?></td>
            <td><code><?= e($log['action']) ?></code></td>
            <td><?= e(($log['entity_type'] ?? '—') . (isset($log['entity_id']) && $log['entity_id'] !== null ? ' · ' . $log['entity_id'] : '')) ?></td>
            <td><code><?= e(json_encode($log['details'] ?? [])) ?></code></td>
            <td><?= e($log['ip_address'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($logs === []): ?><tr><td colspan="6"><div class="empty-table">No activity has been recorded.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
