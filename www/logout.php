<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    logout_admin();
}

header('Location: login.php');
exit;
