<?php
declare(strict_types=1);

require_once __DIR__ . '/branding.php';

function admin_shell_start(string $title, string $active): void
{
    $items = [
        'dashboard' => ['label' => 'Табло', 'href' => 'admin.php'],
        'users' => ['label' => 'Потребители', 'href' => 'admin-users.php'],
        'orders' => ['label' => 'Поръчки', 'href' => 'admin-orders.php'],
        'products' => ['label' => 'Продукти', 'href' => 'admin-products.php'],
        'highlights' => ['label' => 'Промоции', 'href' => 'admin-highlights.php'],
        'reports' => ['label' => 'Справки', 'href' => 'admin-reports.php'],
    ];
    $flashSuccess = get_flash('success');
    $flashError = get_flash('error');
    ?>
    <!doctype html>
    <html lang="bg">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars(site_title($title)) ?></title>
        <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/Main.css">
    </head>
    <body class="bg-soft">
    <div class="container-fluid py-4">
        <div class="row g-3">
            <aside class="col-12 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm admin-sidebar">
                    <div class="card-body">
                        <a class="brand-lockup brand-lockup-admin d-inline-flex align-items-center gap-2 mb-3" href="Main.php" aria-label="<?= htmlspecialchars(site_name()) ?>">
                            <span class="brand-logo-box brand-logo-box-admin">
                                <img class="brand-logo-image" src="<?= htmlspecialchars(site_logo_path()) ?>" alt="">
                            </span>
                            <span class="brand-admin-wordmark"><?= htmlspecialchars(site_wordmark_primary()) ?></span>
                        </a>
                        <h1 class="h5 mb-3">Админ меню</h1>
                        <div class="list-group list-group-flush">
                            <?php foreach ($items as $key => $item): ?>
                                <a class="list-group-item list-group-item-action rounded-3 mb-1 <?= $active === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>">
                                    <?= htmlspecialchars($item['label']) ?>
                                </a>
                            <?php endforeach; ?>
                            <a class="list-group-item list-group-item-action rounded-3 mb-1" href="Main.php">Към сайта</a>
                            <?php if (is_owner()): ?>
                                <a class="list-group-item list-group-item-action rounded-3" target="_blank" href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=project_app">Отвори phpMyAdmin</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </aside>
            <section class="col-12 col-lg-9 col-xl-10">
                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
                <?php endif; ?>
                <?php if ($flashError): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
                <?php endif; ?>
    <?php
}

function admin_shell_end(): void
{
    ?>
            </section>
        </div>
    </div>
    </body>
    </html>
    <?php
}

