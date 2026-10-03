<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/supabase.php';

$config = require __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);

    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']);
}

function current_admin(): array
{
    return is_logged_in() ? $_SESSION['admin'] : [];
}

function role_permissions(): array
{
    return [
        'owner' => ['*'],
        'administrator' => [
            'dashboard.view',
            'users.view',
            'users.manage',
            'subscriptions.view',
            'subscriptions.manage',
            'devices.view',
            'devices.manage',
            'logs.view',
            'permissions.view',
            'permissions.manage',
            'releases.view',
            'releases.manage',
            'settings.view',
            'settings.manage',
            'health.view',
        ],
        'support' => [
            'dashboard.view',
            'users.view',
            'users.manage',
            'subscriptions.view',
            'subscriptions.manage',
            'devices.view',
            'devices.manage',
            'logs.view',
            'health.view',
        ],
        'viewer' => [
            'dashboard.view',
            'users.view',
            'subscriptions.view',
            'devices.view',
            'logs.view',
            'releases.view',
            'health.view',
        ],
    ];
}

function admin_has_permission(string $permission): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $role = (string) ($_SESSION['admin']['role'] ?? 'viewer');
    $permissions = role_permissions()[$role] ?? [];

    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function require_permission(string $permission): void
{
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');

    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}

function authenticate_admin(string $username, string $password): array
{
    global $config;

    $bootstrapUsername = (string) $config['admin_username'];
    $bootstrapHash = (string) $config['admin_password_hash'];

    if ($bootstrapUsername !== '' && $bootstrapHash !== '' && hash_equals($bootstrapUsername, $username) && password_verify($password, $bootstrapHash)) {
        return [
            'id' => 'bootstrap',
            'username' => $bootstrapUsername,
            'role' => 'owner',
            'bootstrap' => true,
        ];
    }

    if (!supabase_is_configured()) {
        throw new RuntimeException('Admin authentication is not configured.');
    }

    $admin = get_admin_user_by_username($username);

    if (!$admin || !(bool) $admin['enabled'] || !password_verify($password, (string) $admin['password_hash'])) {
        return [];
    }

    return [
        'id' => (string) $admin['id'],
        'username' => (string) $admin['username'],
        'role' => (string) $admin['role'],
        'bootstrap' => false,
    ];
}

function login_admin(array $admin): void
{
    session_regenerate_id(true);

    $_SESSION['admin'] = $admin;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logout_admin(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    $next = $_SERVER['REQUEST_URI'] ?? 'index.php';
    if (!str_contains($next, 'login.php')) {
        $_SESSION['login_next'] = safe_return_to($next, 'index.php');
    }

    header('Location: login.php');
    exit;
}

function permission_catalog(): array
{
    return [
        'Dashboard' => ['dashboard.view'],
        'Users' => ['users.view', 'users.manage'],
        'Subscriptions' => ['subscriptions.view', 'subscriptions.manage'],
        'Devices' => ['devices.view', 'devices.manage'],
        'Activity logs' => ['logs.view'],
        'Permissions' => ['permissions.view', 'permissions.manage'],
        'Releases' => ['releases.view', 'releases.manage'],
        'Settings' => ['settings.view', 'settings.manage'],
        'System health' => ['health.view'],
    ];
}
