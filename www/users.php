<?php
declare(strict_types=1);

$pageTitle = 'Users';
$currentPage = 'users';

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="eyebrow">ACCOUNT MANAGEMENT</div>
        <h1>Users</h1>
        <p class="page-subtitle">View and manage Solis accounts.</p>
    </div>
    <a class="button disabled" href="#">Add user</a>
</div>

<section class="panel">
    <div class="panel-toolbar">
        <div class="search-box">
            <input type="search" placeholder="Search username or email" disabled>
        </div>
        <div class="toolbar-note">Database connection pending</div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Last active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr class="empty-row">
                    <td colspan="5">
                        <div class="empty-table">
                            No users to display
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
