<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/cart.php';
require_once __DIR__ . '/Be/includes/branding.php';
require_once __DIR__ . '/Be/includes/product-helpers.php';

$activePage = 'products';
$user = current_user();
$flashSuccess = get_flash('success');
$flashError = get_flash('error');

$stmt = db()->query('SELECT id, name, category, description, image_url, price, stock FROM products ORDER BY id ASC');
$productsRaw = $stmt->fetchAll();
$categories = ['All'];
foreach ($productsRaw as $p) {
    $categories[] = (string)($p['category'] ?: 'Components');
}
$categories = array_values(array_unique($categories));

$selectedCategory = trim((string)($_GET['cat'] ?? 'All'));
if (!in_array($selectedCategory, $categories, true)) {
    $selectedCategory = 'All';
}

$products = [];
foreach ($productsRaw as $product) {
    $product = normalize_product_row($product);
    if ($selectedCategory !== 'All' && $product['category'] !== $selectedCategory) {
        continue;
    }
    $products[] = $product;
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Продукти')) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<?php require __DIR__ . '/Be/includes/site-nav.php'; ?>

<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h1 class="h3 mb-0">Препоръчани продукти</h1>
        <form method="get" class="d-md-none">
            <select class="form-select" name="cat" onchange="this.form.submit()">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= htmlspecialchars(bg_category($cat)) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <aside class="col-md-3 d-none d-md-block">
            <div class="list-group shadow-sm rounded-3">
                <?php foreach ($categories as $cat): ?>
                    <a class="list-group-item list-group-item-action <?= $selectedCategory === $cat ? 'active' : '' ?>" href="products.php?cat=<?= urlencode($cat) ?>">
                        <?= htmlspecialchars(bg_category($cat)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
        <section class="col-12 col-md-9">
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <?php $stockInfo = stock_status_info((int)$product['stock']); ?>
                    <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <img class="product-thumb-img" src="<?= htmlspecialchars(product_image_src($product)) ?>" alt="<?= htmlspecialchars((string)$product['name']) ?>" loading="lazy" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='product-image.php?id=<?= (int)$product['id'] ?>';">
                    <div class="card-body d-flex flex-column">
                        <div class="small text-secondary mb-1"><?= htmlspecialchars(bg_category((string)$product['category'])) ?></div>
                        <h2 class="h5"><?= htmlspecialchars($product['name']) ?></h2>
                        <p class="text-secondary small flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-semibold"><?= number_format((float)$product['price'], 2) ?> лв.</span>
                            <span class="badge <?= htmlspecialchars($stockInfo['class']) ?>"><?= htmlspecialchars($stockInfo['label']) ?></span>
                        </div>
                        <div class="small text-secondary mb-3">Налични бройки: <?= (int)$product['stock'] ?></div>
                        <form action="Be/handlers/cart.php" method="post" class="d-flex gap-2">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <input class="form-control" type="number" min="1" max="<?= (int)$product['stock'] ?>" name="quantity" value="1" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>
                            <button class="btn btn-primary" type="submit" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>Добави</button>
                        </form>
                        <a class="btn btn-outline-secondary mt-2" href="product-view.php?id=<?= (int)$product['id'] ?>">Виж детайли</a>
                    </div>
                </div>
            </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

