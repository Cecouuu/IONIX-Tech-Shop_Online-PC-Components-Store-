<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/branding.php';

require_login();

$user = current_user();
$stmt = db()->prepare('SELECT id, title, message, status, created_at FROM user_requests WHERE user_id = :user_id ORDER BY id DESC');
$stmt->execute(['user_id' => (int)$user['id']]);
$requests = $stmt->fetchAll();
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Моите заявки')) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Моите заявки</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="Main.php">Начало</a>
            <a class="btn btn-outline-secondary" href="products.php">Продукти</a>
            <a class="btn btn-primary" href="request-create.php">Нова заявка</a>
        </div>
    </div>

    <?php if ($flashSuccess = get_flash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>

    <?php if (!$requests): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-secondary">
                Все още няма заявки.
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($requests as $request): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <h2 class="h5 mb-0"><?= htmlspecialchars($request['title']) ?></h2>
                                <span class="badge text-bg-primary"><?= htmlspecialchars($request['status']) ?></span>
                            </div>
                            <p class="mb-2 text-secondary"><?= nl2br(htmlspecialchars($request['message'])) ?></p>
                            <small class="text-secondary">Създадена: <?= htmlspecialchars($request['created_at']) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>

