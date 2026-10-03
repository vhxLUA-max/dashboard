<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): never
{
    if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
        $path = 'index.php';
    }

    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

function render_flashes(): void
{
    foreach (consume_flashes() as $item) {
        $type = isset($item['type']) ? (string) $item['type'] : 'info';
        $message = isset($item['message']) ? (string) $item['message'] : '';
        echo '<div class="alert alert-' . e($type) . '">' . e($message) . '</div>';
    }
}

function post_string(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function query_string(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function post_bool(string $key): bool
{
    return isset($_POST[$key]) && in_array((string) $_POST[$key], ['1', 'true', 'on'], true);
}

function normalize_datetime(?string $value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format(DateTimeInterface::ATOM);
    } catch (Throwable) {
        return null;
    }
}

function datetime_local_value(?string $value): string
{
    if (!$value) {
        return '';
    }

    try {
        $config = require __DIR__ . '/../../config.php';
        $date = new DateTimeImmutable($value);
        $date = $date->setTimezone(new DateTimeZone($config['timezone']));
        return $date->format('Y-m-d\TH:i');
    } catch (Throwable) {
        return '';
    }
}

function format_date(?string $value): string
{
    if (!$value) {
        return '—';
    }

    try {
        $config = require __DIR__ . '/../../config.php';
        $date = new DateTimeImmutable($value);
        $date = $date->setTimezone(new DateTimeZone($config['timezone']));
        return $date->format('M j, Y g:i A');
    } catch (Throwable) {
        return (string) $value;
    }
}

function format_money(mixed $amount, string $currency = 'PHP'): string
{
    return e($currency) . ' ' . number_format((float) $amount, 2);
}

function status_class(string $status): string
{
    return match ($status) {
        'active', 'published', 'paid', 'sent', 'online' => 'success',
        'trialing', 'pending', 'processing', 'draft' => 'warning',
        'disabled', 'revoked', 'canceled', 'expired', 'failed', 'retired', 'past_due', 'unpaid', 'refunded' => 'danger',
        default => 'muted',
    };
}

function safe_return_to(string $value, string $fallback = 'index.php'): string
{
    if ($value === '' || str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
        return $fallback;
    }

    if (!preg_match('#^[A-Za-z0-9_./?=&%\-]+$#', $value)) {
        return $fallback;
    }

    return $value;
}

function badge_html(string $text, ?string $status = null): string
{
    $status = $status ?? strtolower($text);
    return '<span class="badge badge-' . e(status_class($status)) . '">' . e(ucwords(str_replace('_', ' ', $text))) . '</span>';
}
