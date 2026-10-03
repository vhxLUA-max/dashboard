<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();
require_permission('subscriptions.view');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

$pageTitle = 'Subscriptions';
$currentPage = 'subscriptions';
$view = query_string('view', 'subscriptions') === 'plans' ? 'plans' : 'subscriptions';
$editingPlan = null;
$accountPrefill = query_string('account_id');

$accounts = [];
$plans = [];
$subscriptions = [];
$errorMessage = null;

try { $accounts = get_secondary_accounts(); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }
try { $plans = get_subscription_plans(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }
try { $subscriptions = get_subscriptions(); } catch (Throwable $e) { $errorMessage ??= $e->getMessage(); }

$editId = query_string('edit');
if ($view === 'plans' && $editId !== '') {
    foreach ($plans as $plan) {
        if ((string) $plan['id'] === $editId) {
            $editingPlan = $plan;
            break;
        }
    }
}

$now = new DateTimeImmutable('now');
$sevenDays = $now->modify('+7 days');
$activeCount = 0;
$expiringCount = 0;

foreach ($subscriptions as $subscription) {
    if (in_array((string) $subscription['status'], ['active', 'trialing'], true)) {
        $activeCount++;
    }

    if (!empty($subscription['expires_at']) && in_array((string) $subscription['status'], ['active', 'trialing'], true)) {
        try {
            $expiry = new DateTimeImmutable((string) $subscription['expires_at']);
            if ($expiry >= $now && $expiry <= $sevenDays) {
                $expiringCount++;
            }
        } catch (Throwable) {
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><div class="eyebrow">ACCESS MANAGEMENT</div><h1>Subscriptions</h1><p class="page-subtitle">Manage access plans, subscription status, start dates, and expiration.</p></div>
    <div class="tabbar">
        <a class="tab <?= $view === 'subscriptions' ? 'active' : '' ?>" href="subscriptions.php">Subscriptions</a>
        <a class="tab <?= $view === 'plans' ? 'active' : '' ?>" href="subscriptions.php?view=plans">Plans</a>
    </div>
</div>

<?php if ($errorMessage !== null): ?><div class="alert alert-warning"><?= e($errorMessage) ?></div><?php endif; ?>

<?php if ($view === 'subscriptions'): ?>
<section class="stats-grid">
    <article class="stat-card"><div class="stat-label">Active subscriptions</div><div class="stat-value"><?= $activeCount ?></div><div class="stat-note">Active or trialing</div></article>
    <article class="stat-card"><div class="stat-label">Expiring soon</div><div class="stat-value"><?= $expiringCount ?></div><div class="stat-note">Within the next 7 days</div></article>
    <article class="stat-card"><div class="stat-label">Plans</div><div class="stat-value"><?= count($plans) ?></div><div class="stat-note"><?= count(array_filter($plans, static fn (array $plan): bool => (bool) $plan['active'])) ?> active</div></article>
    <article class="stat-card"><div class="stat-label">Total records</div><div class="stat-value"><?= count($subscriptions) ?></div><div class="stat-note">All subscription records</div></article>
</section>

<?php if (admin_has_permission('subscriptions.manage')): ?>
<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">NEW SUBSCRIPTION</div><h2>Create subscription</h2></div></div>
    <div class="panel-body">
        <form class="form-grid form-grid-wide" method="post" action="actions.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="subscription_save">
            <input type="hidden" name="return_to" value="<?= e('subscriptions.php') ?>">
            <label><span>User</span><select name="account_id" required><option value="">Select user</option><?php foreach ($accounts as $account): ?><option value="<?= e($account['id']) ?>" <?= $accountPrefill === (string) $account['id'] ? 'selected' : '' ?>><?= e($account['username']) ?> · <?= e($account['discord_user_id']) ?></option><?php endforeach; ?></select></label>
            <label><span>Plan</span><select name="plan_id"><option value="">No plan</option><?php foreach ($plans as $plan): ?><option value="<?= e($plan['id']) ?>"><?= e($plan['name']) ?> · <?= e($plan['billing_interval']) ?> / <?= e($plan['duration_days'] ?? 'lifetime') ?> days</option><?php endforeach; ?></select></label>
            <label><span>Status</span><select name="status"><option>active</option><option>trialing</option><option>canceled</option><option>expired</option></select></label>
            <label><span>Start</span><input type="datetime-local" name="starts_at"></label>
            <label><span>Expiration</span><input type="datetime-local" name="expires_at"></label>
            <label class="full"><span>Notes</span><textarea name="notes" rows="3"></textarea></label>
            <div class="form-actions full"><button class="button primary" type="submit">Create subscription</button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><div class="eyebrow">CURRENT RECORDS</div><h2>Subscriptions</h2></div></div>
    <div class="table-wrap">
        <table><thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Starts</th><th>Expires</th><th></th></tr></thead><tbody>
        <?php foreach ($subscriptions as $subscription): ?>
            <tr>
                <td><a class="table-link" href="user.php?id=<?= e($subscription['account_id']) ?>"><?= e($subscription['account']['username'] ?? 'Unknown') ?></a></td>
                <td><?= e($subscription['plan']['name'] ?? 'Unassigned') ?></td>
                <td><?= badge_html($subscription['status']) ?></td>
                <td><?= e(format_date($subscription['starts_at'])) ?></td>
                <td><?= e(format_date($subscription['expires_at'])) ?></td>
                <td class="actions">
                    <a class="button small" href="subscription.php?id=<?= e($subscription['id']) ?>">Open</a>
                    <?php if (admin_has_permission('subscriptions.manage')): ?>
                        <form method="post" action="actions.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="subscription_renew"><input type="hidden" name="id" value="<?= e($subscription['id']) ?>"><input type="hidden" name="return_to" value="subscriptions.php"><button class="button small" type="submit">Renew</button></form>
                        <?php if ($subscription['status'] !== 'canceled'): ?><form method="post" action="actions.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="subscription_cancel"><input type="hidden" name="id" value="<?= e($subscription['id']) ?>"><input type="hidden" name="return_to" value="subscriptions.php"><button class="button small" type="submit">Cancel</button></form><?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($subscriptions === []): ?><tr><td colspan="6"><div class="empty-table">No subscriptions have been created.</div></td></tr><?php endif; ?>
        </tbody></table>
    </div>
</section>
<?php else: ?>
<section class="grid-2">
    <?php if (admin_has_permission('subscriptions.manage')): ?>
    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow"><?= $editingPlan ? 'EDIT PLAN' : 'NEW PLAN' ?></div><h2><?= $editingPlan ? e($editingPlan['name']) : 'Create plan' ?></h2></div><?php if ($editingPlan): ?><a class="button" href="subscriptions.php?view=plans">New</a><?php endif; ?></div>
        <div class="panel-body">
            <form class="form-stack" method="post" action="actions.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="plan_save">
                <input type="hidden" name="id" value="<?= e($editingPlan['id'] ?? '') ?>">
                <input type="hidden" name="return_to" value="subscriptions.php?view=plans">
                <label><span>Code</span><input type="text" name="code" value="<?= e($editingPlan['code'] ?? '') ?>" placeholder="monthly" required></label>
                <label><span>Name</span><input type="text" name="name" value="<?= e($editingPlan['name'] ?? '') ?>" placeholder="Solis Monthly" required></label>
                <label><span>Description</span><textarea name="description" rows="3"><?= e($editingPlan['description'] ?? '') ?></textarea></label>
                <div class="form-grid">
                    <label><span>Interval</span><select name="billing_interval"><option value="month" <?= ($editingPlan['billing_interval'] ?? 'month') === 'month' ? 'selected' : '' ?>>Month</option><option value="year" <?= ($editingPlan['billing_interval'] ?? '') === 'year' ? 'selected' : '' ?>>Year</option><option value="lifetime" <?= ($editingPlan['billing_interval'] ?? '') === 'lifetime' ? 'selected' : '' ?>>Lifetime</option></select></label>
                    <label><span>Duration (days)</span><input type="number" name="duration_days" min="1" value="<?= e($editingPlan['duration_days'] ?? '') ?>" placeholder="30"></label>
                </div>
                <label class="checkbox-row"><input type="checkbox" name="active" value="1" <?= (!$editingPlan || (bool) ($editingPlan['active'] ?? false)) ? 'checked' : '' ?>><span>Plan available for new subscriptions</span></label>
                <button class="button primary" type="submit"><?= $editingPlan ? 'Save plan' : 'Create plan' ?></button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-header"><div><div class="eyebrow">CATALOG</div><h2>Plans</h2></div></div>
        <div class="table-wrap">
            <table><thead><tr><th>Plan</th><th>Interval</th><th>Duration</th><th>Status</th><th></th></tr></thead><tbody>
            <?php foreach ($plans as $plan): ?>
                <tr><td><strong><?= e($plan['name']) ?></strong><div class="muted tiny"><?= e($plan['code']) ?></div></td><td><?= e($plan['billing_interval']) ?></td><td><?= e($plan['duration_days'] ?? 'Lifetime') ?><?= $plan['duration_days'] !== null ? ' days' : '' ?></td><td><?= badge_html($plan['active'] ? 'active' : 'disabled', $plan['active'] ? 'active' : 'disabled') ?></td><td class="actions"><?php if (admin_has_permission('subscriptions.manage')): ?><a class="button small" href="subscriptions.php?view=plans&edit=<?= e($plan['id']) ?>">Edit</a><form method="post" action="actions.php" onsubmit="return confirm('Delete this plan?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="plan_delete"><input type="hidden" name="id" value="<?= e($plan['id']) ?>"><input type="hidden" name="return_to" value="subscriptions.php?view=plans"><button class="button small danger" type="submit">Delete</button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if ($plans === []): ?><tr><td colspan="5"><div class="empty-table">No plans yet. Create the first subscription plan.</div></td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
