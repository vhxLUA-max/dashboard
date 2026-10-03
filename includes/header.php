<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Solis Admin', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="content">
