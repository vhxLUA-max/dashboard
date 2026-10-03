<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$admin = current_admin();
$settings = [];
try {
    if (supabase_is_configured()) {
        $settings = get_settings();
    }
} catch (Throwable) {
}
$brandName = $settings['site_name'] ?? 'Solis Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= e(($pageTitle ?? 'Dashboard') . ' · ' . $brandName) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="content">
        <?php render_flashes(); ?>
