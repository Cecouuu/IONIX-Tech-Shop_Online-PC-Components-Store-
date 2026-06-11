<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/cart.php';
require_once __DIR__ . '/Be/includes/branding.php';

$activePage = 'cart';
$user = current_user();
$flashSuccess = get_flash('success');
$flashError = get_flash('error');
$cart = cart_items();

$cartRows = [];
$grandTotal = 0.0;

if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, price, stock FROM products WHERE id IN ({$placeholders})");
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $product) {
        $products[(int)$product['id']] = $product;
    }

    foreach ($cart as $productId => $qty) {
        if (!isset($products[$productId])) {
            continue;
        }
        $price = (float)$products[$productId]['price'];
        $lineTotal = $price * $qty;
        $grandTotal += $lineTotal;
        $cartRows[] = [
            'id' => $productId,
            'name' => $products[$productId]['name'],
            'price' => $price,
            'stock' => (int)$products[$productId]['stock'],
            'qty' => $qty,
            'line_total' => $lineTotal,
        ];
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Количка')) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<?php require __DIR__ . '/Be/includes/site-nav.php'; ?>
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Количка</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="products.php">Продължи пазаруването</a>
            <a class="btn btn-outline-secondary" href="Main.php">Начало</a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <?php if (!$cartRows): ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-4 text-secondary">Количката е празна.</div></div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Цена</th>
                            <th style="width: 210px;">Количество</th>
                            <th>Общо</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <form class="d-flex gap-2" method="post" action="Be/handlers/cart.php">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                                        <input class="form-control form-control-sm" type="number" min="0" max="<?= (int)$row['stock'] ?>" name="quantity" value="<?= (int)$row['qty'] ?>">
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Обнови</button>
                                    </form>
                                </td>
                                <td>$<?= number_format($row['line_total'], 2) ?></td>
                                <td>
                                    <form method="post" action="Be/handlers/cart.php">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Премахни</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <form method="post" action="Be/handlers/cart.php">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-outline-secondary" type="submit">Изчисти количката</button>
            </form>
            <div class="d-flex align-items-center gap-3">
                <div class="h5 mb-0">Крайна сума: $<?= number_format($grandTotal, 2) ?></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Задължителни данни за поръчката</h2>
                <form method="post" action="Be/handlers/cart.php">
                    <input type="hidden" name="action" value="checkout">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="buyer_name">Име и фамилия</label>
                            <input class="form-control" id="buyer_name" name="buyer_name" type="text" value="<?= htmlspecialchars((string)($user['name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="buyer_email">Имейл</label>
                            <input class="form-control" id="buyer_email" name="buyer_email" type="email" value="<?= htmlspecialchars((string)($user['email'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="buyer_phone">Телефон</label>
                            <input class="form-control" id="buyer_phone" name="buyer_phone" type="text" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="buyer_address">Адрес</label>
                            <input class="form-control" id="buyer_address" name="buyer_address" type="text" required>
                        </div>
                    </div>
                    <div class="mt-3 d-grid d-md-flex justify-content-md-end">
                        <button class="btn btn-dark px-4" type="submit">Завърши поръчката</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

