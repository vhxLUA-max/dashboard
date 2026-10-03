<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try { create_activity_log('admin.logout', 'admin', (string) (current_admin()['id'] ?? '')); } catch (Throwable) {}
    logout_admin();
}

header('Location: login.php');
exit;
