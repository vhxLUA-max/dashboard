<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access denied</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-card">
        <div class="eyebrow">403</div>
        <h1>Access denied</h1>
        <p class="page-subtitle">Your admin role does not have permission to perform this action.</p>
        <a class="button primary button-block" href="index.php">Return to dashboard</a>
    </section>
</main>
</body>
</html>
