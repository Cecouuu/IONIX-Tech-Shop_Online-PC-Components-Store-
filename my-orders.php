<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/branding.php';

require_login();

$user = current_user();
$flashSuccess = get_flash('success');
$flashError = get_flash('error');

$orderStmt = db()->prepare('
    SELECT id, total_amount, created_at
    FROM orders
    WHERE user_id = :user_id
    ORDER BY id DESC
');
$orderStmt->execute(['user_id' => (int)$user['id']]);
$orders = $orderStmt->fetchAll();

$itemsByOrder = [];
if ($orders) {
    $orderIds = array_map(static fn(array $o): int => (int)$o['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $itemsStmt = db()->prepare("
        SELECT oi.order_id, oi.quantity, oi.unit_price, p.name
        FROM order_items oi
        INNER JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id IN ({$placeholders})
        ORDER BY oi.id ASC
    ");
    $itemsStmt->execute($orderIds);
    $items = $itemsStmt->fetchAll();

    foreach ($items as $item) {
        $itemsByOrder[(int)$item['order_id']][] = $item;
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Моите поръчки')) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Моите поръчки</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="Main.php">Начало</a>
            <?php if (is_admin()): ?>
                <a class="btn btn-warning" href="admin.php">Админ панел</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="products.php">Купи още</a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <?php if (!$orders): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-secondary">Все още няма покупки.</div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h2 class="h5 mb-0">Поръчка #<?= (int)$order['id'] ?></h2>
                                <span class="fw-semibold">$<?= number_format((float)$order['total_amount'], 2) ?></span>
                            </div>
                            <p class="text-secondary small mb-3">Създадена на <?= htmlspecialchars($order['created_at']) ?></p>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($itemsByOrder[(int)$order['id']] ?? [] as $item): ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span><?= htmlspecialchars($item['name']) ?> x<?= (int)$item['quantity'] ?></span>
                                        <span>$<?= number_format((float)$item['unit_price'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>

