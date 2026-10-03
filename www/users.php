<?php
declare(strict_types=1);

require __DIR__ . '/includes/supabase.php';

$pageTitle = 'Users';
$currentPage = 'users';

$accounts = [];
$errorMessage = null;

if (supabase_is_configured()) {
    try {
        $accounts = get_secondary_accounts();
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
} else {
    $errorMessage = 'Set SUPABASE_SECRET_KEY in the hosting environment.';
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="eyebrow">ACCOUNT MANAGEMENT</div>
        <h1>Users</h1>
        <p class="page-subtitle">View Solis secondary accounts.</p>
    </div>
</div>

<?php if ($errorMessage !== null): ?>
    <div class="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="panel">
    <div class="panel-toolbar">
        <div class="search-box">
            <input type="search" placeholder="Search username or Discord ID" disabled>
        </div>
        <div class="toolbar-note"><?= count($accounts) ?> account<?= count($accounts) === 1 ? '' : 's' ?></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Discord ID</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($accounts === []): ?>
                    <tr class="empty-row">
                        <td colspan="5">
                            <div class="empty-table">No users to display</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $account['username'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $account['discord_user_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $account['enabled'] ? 'Active' : 'Disabled' ?></td>
                            <td><?= htmlspecialchars((string) $account['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $account['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
