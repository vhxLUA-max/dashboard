<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    redirect_to('index.php');
}

$errorMessage = null;
$next = safe_return_to(query_string('next', $_SESSION['login_next'] ?? 'index.php'), 'index.php');
unset($_SESSION['login_next']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_string('username');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorMessage = 'Enter your admin username and password.';
    } else {
        try {
            $admin = authenticate_admin($username, $password);

            if ($admin === []) {
                $errorMessage = 'Invalid username or password.';
            } else {
                login_admin($admin);
                try { create_activity_log('admin.login', 'admin', (string) ($admin['id'] ?? '')); } catch (Throwable) {}
                flash('success', 'Signed in successfully.');
                redirect_to($next);
            }
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solis Admin Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-card">
        <div class="login-brand">
            <span class="brand-mark">S</span>
            <div>
                <strong>Solis</strong>
                <small>Admin Control</small>
            </div>
        </div>

        <div class="eyebrow">SECURE ACCESS</div>
        <h1>Admin login</h1>
        <p class="page-subtitle">Sign in to manage Solis accounts and services.</p>

        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <form class="form-stack" method="post">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label>
                <span>Username</span>
                <input type="text" name="username" autocomplete="username" required autofocus>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="button primary button-block" type="submit">Sign in</button>
        </form>

        <div class="login-note">
            The bootstrap owner uses <code>SOLIS_ADMIN_USERNAME</code> and <code>SOLIS_ADMIN_PASSWORD_HASH</code>. Additional admins can be created from Permissions.
        </div>
    </section>
</main>
</body>
</html>
