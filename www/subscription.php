<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('subscriptions.view');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/supabase.php';

$id = query_string('id');
$subscription = $id !== '' ? get_subscription($id) : null;

if (!$subscription) {
    redirect_to('subscriptions.php');
}

$plans = get_subscription_plans();
$accounts = get_secondary_accounts();
$events = get_subscription_events($id);

$pageTitle = 'Subscription details';
$currentPage = 'subscriptions';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">SUBSCRIPTION</div><h1><?= e($subscription['plan']['name'] ?? 'Unassigned') ?></h1><p class="page-subtitle"><?= e($subscription['account']['username'] ?? 'Unknown user') ?></p></div>
    <a class="button" href="subscriptions.php">Back to subscriptions</a>
</div>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">EDIT</div><h2>Subscription</h2></div><div><?= badge_html($subscription['status']) ?></div></div>
    <?php if (admin_has_permission('subscriptions.manage')): ?>
    <div class="panel-body">
        <form class="form-grid form-grid-wide" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="subscription_save">
            <input type="hidden" name="id" value="<?= e($subscription['id']) ?>">
            <input type="hidden" name="return_to" value="<?= e('subscription.php?id=' . $subscription['id']) ?>">
            <label><span>User</span><select name="account_id" required><?php foreach ($accounts as $account): ?><option value="<?= e($account['id']) ?>" <?= (string) $subscription['account_id'] === (string) $account['id'] ? 'selected' : '' ?>><?= e($account['username']) ?></option><?php endforeach; ?></select></label>
            <label><span>Plan</span><select name="plan_id"><option value="">No plan</option><?php foreach ($plans as $plan): ?><option value="<?= e($plan['id']) ?>" <?= (string) ($subscription['plan_id'] ?? '') === (string) $plan['id'] ? 'selected' : '' ?>><?= e($plan['name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Status</span><select name="status"><?php foreach (['active','trialing','past_due','canceled','expired'] as $status): ?><option value="<?= e($status) ?>" <?= $subscription['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
            <label><span>Start</span><input type="datetime-local" name="starts_at" value="<?= e(datetime_local_value($subscription['starts_at'])) ?>"></label>
            <label><span>Expiration</span><input type="datetime-local" name="expires_at" value="<?= e(datetime_local_value($subscription['expires_at'])) ?>"></label>
            <label><span>Payment status</span><select name="payment_status"><?php foreach (['unpaid','pending','paid','refunded'] as $status): ?><option value="<?= e($status) ?>" <?= $subscription['payment_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
            <label><span>Amount paid</span><input type="number" name="amount_paid" min="0" step="0.01" value="<?= e($subscription['amount_paid']) ?>"></label>
            <label class="checkbox-row"><input type="checkbox" name="auto_renew" value="1" <?= $subscription['auto_renew'] ? 'checked' : '' ?>><span>Auto renew</span></label>
            <label><span>Payment reference</span><input type="text" name="payment_reference" value="<?= e($subscription['payment_reference']) ?>"></label>
            <label><span>External reference</span><input type="text" name="external_reference" value="<?= e($subscription['external_reference']) ?>"></label>
            <label class="full"><span>Notes</span><textarea name="notes" rows="4"><?= e($subscription['notes']) ?></textarea></label>
            <div class="form-actions full"><button class="button primary" type="submit">Save subscription</button></div>
        </form>
    </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">HISTORY</div><h2>Subscription events</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Event</th><th>Status</th><th>Details</th><th>Time</th></tr></thead><tbody>
    <?php foreach ($events as $event): ?>
        <tr><td><?= e($event['event_type']) ?></td><td><?= e(($event['old_status'] ?? '—') . ' → ' . ($event['new_status'] ?? '—')) ?></td><td><code><?= e(json_encode($event['details'] ?? [])) ?></code></td><td><?= e(format_date($event['created_at'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if ($events === []): ?><tr><td colspan="4"><div class="empty-table">No events recorded.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>

<?php if (admin_has_permission('subscriptions.manage')): ?>
<section class="danger-zone panel">
    <div class="panel-header"><div><div class="eyebrow">DANGER ZONE</div><h2>Delete subscription</h2></div></div>
    <div class="panel-body split">
        <p class="muted">This removes the subscription and its event history.</p>
        <form method="post" action="actions.php" onsubmit="return confirm('Delete this subscription?');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="subscription_delete">
            <input type="hidden" name="id" value="<?= e($subscription['id']) ?>">
            <input type="hidden" name="return_to" value="subscriptions.php">
            <button class="button danger" type="submit">Delete subscription</button>
        </form>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
