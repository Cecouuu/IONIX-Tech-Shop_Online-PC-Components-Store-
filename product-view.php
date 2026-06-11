<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/cart.php';
require_once __DIR__ . '/Be/includes/branding.php';
require_once __DIR__ . '/Be/includes/product-helpers.php';

$activePage = 'products';
$flashSuccess = get_flash('success');
$flashError = get_flash('error');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('
    SELECT id, name, category, description, image_url, price, stock
    FROM products
    WHERE id = :id
    LIMIT 1
');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Продуктът не е намерен.');
    header('Location: products.php');
    exit;
}

$product = normalize_product_row($product);
$stock = (int)$product['stock'];
$stockInfo = stock_status_info($stock);
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title((string)$product['name'])) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<?php require __DIR__ . '/Be/includes/site-nav.php'; ?>

<main class="container py-4 py-lg-5">
    <a class="btn btn-link text-decoration-none ps-0 mb-3" href="products.php">&larr; Назад към магазина</a>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-3 p-lg-4">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="detail-image-frame">
                    <img class="detail-image" src="<?= htmlspecialchars(product_image_src($product)) ?>" alt="<?= htmlspecialchars((string)$product['name']) ?>" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='product-image.php?id=<?= (int)$product['id'] ?>';">
                </div>
            </div>
            <div class="col-lg-6 d-flex flex-column">
                <span class="badge bg-light text-dark align-self-start mb-3"><?= htmlspecialchars(bg_category((string)$product['category'])) ?></span>
                <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars((string)$product['name']) ?></h1>
                <p class="text-secondary mb-3"><?= htmlspecialchars((string)$product['description']) ?></p>
                <div class="d-flex align-items-center gap-2 mb-4">
                    <div class="h1 mb-0"><?= number_format((float)$product['price'], 2) ?> лв.</div>
                    <span class="badge <?= htmlspecialchars($stockInfo['class']) ?>"><?= htmlspecialchars($stockInfo['label']) ?></span>
                </div>
                <p class="small text-secondary mb-4">Налични бройки: <?= $stock ?>. <?= htmlspecialchars($stockInfo['description']) ?></p>

                <form action="Be/handlers/cart.php" method="post" class="mt-auto">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="qty">Количество</label>
                        <div class="input-group qty-group">
                            <button class="btn btn-outline-secondary" type="button" id="minusBtn">-</button>
                            <input class="form-control text-center" id="qty" type="number" min="1" max="<?= $stock ?>" name="quantity" value="1" <?= $stock < 1 ? 'disabled' : '' ?>>
                            <button class="btn btn-outline-secondary" type="button" id="plusBtn">+</button>
                        </div>
                    </div>
                    <button class="btn btn-dark btn-lg w-100" type="submit" <?= $stock < 1 ? 'disabled' : '' ?>>Добави в количката</button>
                </form>

                <div class="alert alert-primary mt-3 mb-0 small">
                    Гаранция за продукта: 2 години гаранция и 30 дни право на връщане.
                </div>
            </div>
        </div>
    </div>
</main>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  (function () {
    const input = document.getElementById('qty');
    const minus = document.getElementById('minusBtn');
    const plus = document.getElementById('plusBtn');
    if (!input || !minus || !plus) return;
    minus.addEventListener('click', function () {
      const min = parseInt(input.min || '1', 10);
      const current = parseInt(input.value || '1', 10);
      input.value = String(Math.max(min, current - 1));
    });
    plus.addEventListener('click', function () {
      const max = parseInt(input.max || '9999', 10);
      const current = parseInt(input.value || '1', 10);
      input.value = String(Math.min(max, current + 1));
    });
  })();
</script>
</body>
</html>

