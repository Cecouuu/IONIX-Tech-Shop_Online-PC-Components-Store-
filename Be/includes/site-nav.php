<?php
declare(strict_types=1);

require_once __DIR__ . '/branding.php';

$activePage = $activePage ?? 'home';
$navUser = current_user();
$navCart = cart_items();
$navCartCount = cart_count();

$navMiniCartRows = [];
$navMiniSubtotal = 0.0;
if ($navCart) {
    $navIds = array_keys($navCart);
    $navPlaceholders = implode(',', array_fill(0, count($navIds), '?'));
    $navStmt = db()->prepare("SELECT id, name, price FROM products WHERE id IN ({$navPlaceholders})");
    $navStmt->execute($navIds);
    $navProductsById = [];
    foreach ($navStmt->fetchAll() as $navProductRow) {
        $navProductsById[(int)$navProductRow['id']] = $navProductRow;
    }
    foreach ($navCart as $navProductId => $navQuantity) {
        if (!isset($navProductsById[$navProductId])) {
            continue;
        }
        $navPrice = (float)$navProductsById[$navProductId]['price'];
        $navLine = $navPrice * $navQuantity;
        $navMiniSubtotal += $navLine;
        $navMiniCartRows[] = [
            'id' => $navProductId,
            'name' => $navProductsById[$navProductId]['name'],
            'qty' => $navQuantity,
            'price' => $navPrice,
        ];
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light border-bottom nav-surface py-3">
    <div class="container">
        <a class="navbar-brand brand-lockup d-flex align-items-center gap-2" href="Main.php" aria-label="<?= htmlspecialchars(site_name()) ?>">
            <span class="brand-logo-box brand-logo-box-nav">
                <img class="brand-logo-image" src="<?= htmlspecialchars(site_logo_path()) ?>" alt="">
            </span>
            <span class="brand-nav-wordmark"><?= htmlspecialchars(site_wordmark_primary()) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav ms-lg-3 me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="Main.php">Начало</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'products' ? 'active' : '' ?>" href="products.php">Продукти</a></li>
                <?php if ($navUser): ?>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'orders' ? 'active' : '' ?>" href="my-orders.php">Моите поръчки</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <button class="btn nav-icon-btn position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-label="Отвори количката">
                    <svg class="nav-icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="19" r="1.35"></circle>
                        <circle cx="17" cy="19" r="1.35"></circle>
                        <path d="M3 4h2.5l1.8 9h10.2l2.1-7H6.2"></path>
                    </svg>
                    <?php if ($navCartCount > 0): ?><span class="badge rounded-pill bg-primary nav-cart-badge"><?= $navCartCount ?></span><?php endif; ?>
                </button>

                <div class="dropdown">
                    <button class="btn nav-icon-btn" type="button" data-bs-toggle="dropdown" aria-label="Профилно меню">
                        <svg class="nav-icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.25"></circle>
                            <path d="M5 20c1.5-3.1 4-4.7 7-4.7s5.5 1.6 7 4.7"></path>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($navUser): ?>
                            <li><h6 class="dropdown-header"><?= htmlspecialchars((string)$navUser['name']) ?></h6></li>
                            <li><a class="dropdown-item" href="my-orders.php">Моите поръчки</a></li>
                            <li><a class="dropdown-item" href="my-requests.php">Моите заявки</a></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="admin.php">Админ панел</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Изход</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="login.php">Вход</a></li>
                            <li><a class="dropdown-item" href="register.php">Регистрация</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="cartDrawerLabel">Вашата количка</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Затвори"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php if (!$navMiniCartRows): ?>
            <p class="text-secondary">Количката е празна.</p>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($navMiniCartRows as $item): ?>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                <small class="text-secondary">Брой: <?= (int)$item['qty'] ?></small>
                            </div>
                            <div class="fw-semibold"><?= number_format($item['price'] * $item['qty'], 2) ?> лв.</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-auto pt-3 border-top">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-secondary">Междинна сума</span>
                    <strong><?= number_format($navMiniSubtotal, 2) ?> лв.</strong>
                </div>
                <a class="btn btn-dark w-100" href="cart.php">Отвори количката</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
unset(
    $navUser,
    $navCart,
    $navCartCount,
    $navMiniCartRows,
    $navMiniSubtotal,
    $navIds,
    $navPlaceholders,
    $navStmt,
    $navProductsById,
    $navProductRow,
    $navProductId,
    $navQuantity,
    $navPrice,
    $navLine
);

