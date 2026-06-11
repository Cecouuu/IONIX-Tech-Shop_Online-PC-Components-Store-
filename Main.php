<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/cart.php';
require_once __DIR__ . '/Be/includes/branding.php';
require_once __DIR__ . '/Be/includes/product-helpers.php';

$activePage = 'home';
$user = current_user();
$flashSuccess = get_flash('success');
$flashError = get_flash('error');

$weeklyProducts = db()->query('
    SELECT p.id, p.name, p.category, p.description, p.image_url, p.price, p.stock, h.label
    FROM product_highlights h
    INNER JOIN products p ON p.id = h.product_id
    WHERE h.highlight_type = "weekly" AND h.is_active = 1
    ORDER BY h.created_at DESC
    LIMIT 4
')->fetchAll();
$weeklyProducts = array_map('normalize_product_row', $weeklyProducts);

$weekendProducts = db()->query('
    SELECT p.id, p.name, p.category, p.description, p.image_url, p.price, p.stock, h.label, h.discount_percent
    FROM product_highlights h
    INNER JOIN products p ON p.id = h.product_id
    WHERE h.highlight_type = "weekend" AND h.is_active = 1
    ORDER BY h.discount_percent DESC, h.created_at DESC
    LIMIT 4
')->fetchAll();
$weekendProducts = array_map('normalize_product_row', $weekendProducts);

if (!$weeklyProducts) {
    $weeklyProducts = db()->query('
        SELECT id, name, category, description, image_url, price, stock, "Топ избор" AS label
        FROM products
        ORDER BY stock DESC, id DESC
        LIMIT 4
    ')->fetchAll();
    $weeklyProducts = array_map('normalize_product_row', $weeklyProducts);
}
if (!$weekendProducts) {
    $weekendProducts = db()->query('
        SELECT id, name, category, description, image_url, price, stock, "Уикенд оферта" AS label, 10.00 AS discount_percent
        FROM products
        ORDER BY id DESC
        LIMIT 4
    ')->fetchAll();
    $weekendProducts = array_map('normalize_product_row', $weekendProducts);
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title()) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft page-shell">
<?php require __DIR__ . '/Be/includes/site-nav.php'; ?>

<main class="container py-5 page-main">
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <section class="card border-0 shadow-sm home-hero mb-5">
        <div class="card-body p-4 p-lg-5 text-center">
            <div class="home-hero-copy mx-auto">
                <p class="home-hero-kicker text-uppercase fw-semibold text-primary-emphasis mb-2">IONIX Tech Shop</p>
                <h1 class="home-hero-title fw-semibold mb-3">Компоненти за мощни конфигурации и готови ъпгрейди</h1>
                <p class="home-hero-lead text-secondary mb-4">Избери видео карта, процесор, памет, SSD или кутия за следващата си конфигурация. Подбрали сме налични компоненти, седмични оферти и уикенд намаления на едно място.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a class="btn btn-primary rounded-pill px-4" href="products.php">Разгледай магазина</a>
                    <a class="btn btn-outline-primary rounded-pill px-4" href="#weekend-deals">Уикенд оферти</a>
                    <?php if ($user): ?>
                        <a class="btn btn-outline-secondary rounded-pill px-4" href="my-orders.php">Моите поръчки</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="store-section-header text-center mb-4">
            <h2 class="h3 mb-2">Продукти на седмицата</h2>
            <p class="text-secondary mb-0">Най-търсените предложения в момента, подбрани от каталога.</p>
            <?php if (is_admin()): ?>
                <a class="btn btn-sm btn-outline-primary mt-3" href="admin-highlights.php">Управление на акцентите</a>
            <?php endif; ?>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($weeklyProducts as $product): ?>
                <?php $stockInfo = stock_status_info((int)$product['stock']); ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <img class="product-thumb-img" src="<?= htmlspecialchars(product_image_src($product)) ?>" alt="<?= htmlspecialchars((string)$product['name']) ?>" loading="lazy" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='product-image.php?id=<?= (int)$product['id'] ?>';">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-primary align-self-start mb-2"><?= htmlspecialchars((string)$product['label']) ?></span>
                            <div class="small text-secondary mb-1"><?= htmlspecialchars(bg_category((string)$product['category'])) ?></div>
                            <h3 class="h6"><?= htmlspecialchars((string)$product['name']) ?></h3>
                            <p class="small text-secondary flex-grow-1"><?= htmlspecialchars((string)$product['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold"><?= number_format((float)$product['price'], 2) ?> лв.</div>
                                <span class="badge <?= htmlspecialchars($stockInfo['class']) ?>"><?= htmlspecialchars($stockInfo['label']) ?></span>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm" href="product-view.php?id=<?= (int)$product['id'] ?>">Виж</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="weekend-deals">
        <div class="store-section-header text-center mb-4">
            <h2 class="h3 mb-2">Уикенд намаления</h2>
            <p class="text-secondary mb-0">Специални цени за избрани компоненти с видим процент на отстъпка.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($weekendProducts as $product): ?>
                <?php
                $discountPct = (float)($product['discount_percent'] ?? 0);
                $discountPrice = (float)$product['price'] * (1 - ($discountPct / 100));
                $stockInfo = stock_status_info((int)$product['stock']);
                ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <img class="product-thumb-img" src="<?= htmlspecialchars(product_image_src($product)) ?>" alt="<?= htmlspecialchars((string)$product['name']) ?>" loading="lazy" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='product-image.php?id=<?= (int)$product['id'] ?>';">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-danger align-self-start mb-2">-<?= number_format($discountPct, 0) ?>%</span>
                            <h3 class="h6"><?= htmlspecialchars((string)$product['name']) ?></h3>
                            <p class="small text-secondary flex-grow-1"><?= htmlspecialchars((string)$product['description']) ?></p>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fw-semibold"><?= number_format($discountPrice, 2) ?> лв.</span>
                                <small class="text-decoration-line-through text-secondary"><?= number_format((float)$product['price'], 2) ?> лв.</small>
                            </div>
                            <span class="badge <?= htmlspecialchars($stockInfo['class']) ?> align-self-start mb-2"><?= htmlspecialchars($stockInfo['label']) ?></span>
                            <a class="btn btn-dark btn-sm" href="product-view.php?id=<?= (int)$product['id'] ?>">Виж офертата</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer class="border-top py-5 footer-surface site-footer-fixed">
    <div class="container">
        <div class="row g-4 pb-4">
            <div class="col-lg-4">
                <a class="brand-lockup footer-lockup d-inline-flex align-items-center gap-2 mb-3 text-decoration-none" href="Main.php" aria-label="<?= htmlspecialchars(site_name()) ?>">
                    <span class="brand-logo-box brand-logo-box-footer">
                        <img class="brand-logo-image" src="<?= htmlspecialchars(site_logo_path()) ?>" alt="">
                    </span>
                    <span class="brand-footer-wordmark">
                        <span class="brand-footer-primary"><?= htmlspecialchars(site_wordmark_primary()) ?></span>
                        <span class="brand-footer-secondary"><?= htmlspecialchars(site_wordmark_secondary()) ?></span>
                    </span>
                </a>
                <p class="text-secondary mb-0">Премиум технологични продукти за хора, които ценят качество и иновации.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h5 mb-3">Магазин</h3>
                <ul class="list-unstyled m-0">
                    <li><a class="footer-link" href="#">Нови продукти</a></li>
                    <li><a class="footer-link" href="#">Най-продавани</a></li>
                    <li><a class="footer-link" href="#">Разпродажба</a></li>
                    <li><a class="footer-link" href="#">Подаръчни карти</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h5 mb-3">Поддръжка</h3>
                <ul class="list-unstyled m-0">
                    <li><a class="footer-link" href="#">Свържете се с нас</a></li>
                    <li><a class="footer-link" href="#">ЧЗВ</a></li>
                    <li><a class="footer-link" href="#">Информация за доставка</a></li>
                    <li><a class="footer-link" href="#">Връщане</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h3 class="h5 mb-3">Бюлетин</h3>
                <p class="text-secondary mb-3">Получавайте новини за нови продукти и ексклузивни оферти.</p>
                <form class="d-flex gap-2">
                    <input class="form-control rounded-4" type="email" placeholder="Вашият имейл" aria-label="Вашият имейл">
                    <button class="btn btn-primary rounded-4 px-4" type="button">Абонирай се</button>
                </form>
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 pt-3 border-top text-secondary">
            <p class="mb-0">&copy; 2026 <?= htmlspecialchars(site_name()) ?>. Всички права запазени.</p>
            <div class="d-flex gap-3">
                <a class="footer-link" href="#">Политика за поверителност</a>
                <a class="footer-link" href="#">Условия за ползване</a>
                <a class="footer-link" href="#">Бисквитки</a>
            </div>
        </div>
    </div>
</footer>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

