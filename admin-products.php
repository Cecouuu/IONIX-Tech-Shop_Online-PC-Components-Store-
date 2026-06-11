<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/admin-shell.php';
require_once __DIR__ . '/Be/includes/product-helpers.php';

require_admin();

$products = db()->query('
    SELECT
        p.id,
        p.name,
        p.category,
        p.description,
        p.image_url,
        p.price,
        p.stock,
        p.created_at,
        hw.discount_percent AS weekly_discount_percent,
        hw.label AS weekly_label,
        hw.is_active AS weekly_active,
        he.discount_percent AS weekend_discount_percent,
        he.label AS weekend_label,
        he.is_active AS weekend_active
    FROM products p
    LEFT JOIN product_highlights hw
      ON hw.product_id = p.id AND hw.highlight_type = "weekly"
    LEFT JOIN product_highlights he
      ON he.product_id = p.id AND he.highlight_type = "weekend"
    ORDER BY p.id DESC
')->fetchAll();
$products = array_map('normalize_product_row', $products);

admin_shell_start('Админ продукти', 'products');
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h4 mb-2">Добавяне на нов продукт</h2>
        <p class="text-secondary mb-4">Можеш да зададеш изображение чрез линк или да качиш файл от компютъра. Ако и двете са попълнени, ще се използва каченият файл.</p>

        <form class="row g-3" method="post" action="Be/handlers/admin-products.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="return_to" value="admin-products.php">
            <div class="col-lg-3">
                <label class="form-label">Име на продукт</label>
                <input class="form-control" type="text" name="name" required>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Категория</label>
                <input class="form-control" type="text" name="category" value="Компоненти" required>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Кратко описание</label>
                <input class="form-control" type="text" name="description" required>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Цена</label>
                <input class="form-control" type="number" step="0.01" min="0.01" name="price" required>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Наличност</label>
                <input class="form-control" type="number" min="0" name="stock" required>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Линк към изображение</label>
                <input class="form-control" type="text" name="image_url" placeholder="https://...">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Качи снимка</label>
                <input class="form-control" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif">
            </div>
            <div class="col-lg-2 d-grid align-self-end">
                <button class="btn btn-primary" type="submit">Добави продукт</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($products as $product): ?>
        <?php $stockInfo = stock_status_info((int)$product['stock']); ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="Be/handlers/admin-products.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="return_to" value="admin-products.php">
                        <input type="hidden" name="current_image_url" value="<?= htmlspecialchars((string)$product['image_url']) ?>">

                        <div class="row g-4">
                            <div class="col-lg-3">
                                <img class="admin-product-image mb-3" src="<?= htmlspecialchars(product_image_src($product)) ?>" alt="<?= htmlspecialchars((string)$product['name']) ?>" onerror="this.onerror=null;this.src='product-image.php?id=<?= (int)$product['id'] ?>';">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge <?= htmlspecialchars($stockInfo['class']) ?>"><?= htmlspecialchars($stockInfo['label']) ?></span>
                                    <span class="small text-secondary">Бройки: <?= (int)$product['stock'] ?></span>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Име</label>
                                        <input class="form-control" type="text" name="name" value="<?= htmlspecialchars((string)$product['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Категория</label>
                                        <input class="form-control" type="text" name="category" value="<?= htmlspecialchars((string)$product['category']) ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Описание</label>
                                        <input class="form-control" type="text" name="description" value="<?= htmlspecialchars((string)$product['description']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Цена</label>
                                        <input class="form-control" type="number" min="0.01" step="0.01" name="price" value="<?= htmlspecialchars((string)$product['price']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Наличност</label>
                                        <input class="form-control" type="number" min="0" name="stock" value="<?= (int)$product['stock'] ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Линк към изображение</label>
                                        <input class="form-control" type="text" name="image_url" value="<?= htmlspecialchars((string)$product['image_url']) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Смени със снимка от компютъра</label>
                                        <input class="form-control" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="border rounded-3 p-3 bg-light-subtle mb-3">
                                    <h3 class="h6 mb-3">Седмична промоция</h3>
                                    <div class="mb-2">
                                        <label class="form-label">Намаление %</label>
                                        <input class="form-control form-control-sm" type="number" min="0" max="90" step="0.01" name="weekly_discount_percent" value="<?= htmlspecialchars((string)($product['weekly_discount_percent'] ?? '0')) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Етикет</label>
                                        <input class="form-control form-control-sm" type="text" name="weekly_label" value="<?= htmlspecialchars((string)($product['weekly_label'] ?? '')) ?>" placeholder="Продукт на седмицата">
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="weekly_active" id="weekly_<?= (int)$product['id'] ?>" <?= (int)($product['weekly_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="weekly_<?= (int)$product['id'] ?>">Активна</label>
                                    </div>
                                </div>

                                <div class="border rounded-3 p-3 bg-light-subtle mb-3">
                                    <h3 class="h6 mb-3">Уикенд промоция</h3>
                                    <div class="mb-2">
                                        <label class="form-label">Намаление %</label>
                                        <input class="form-control form-control-sm" type="number" min="0" max="90" step="0.01" name="weekend_discount_percent" value="<?= htmlspecialchars((string)($product['weekend_discount_percent'] ?? '0')) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Етикет</label>
                                        <input class="form-control form-control-sm" type="text" name="weekend_label" value="<?= htmlspecialchars((string)($product['weekend_label'] ?? '')) ?>" placeholder="Уикенд оферта">
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="weekend_active" id="weekend_<?= (int)$product['id'] ?>" <?= (int)($product['weekend_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="weekend_<?= (int)$product['id'] ?>">Активна</label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" type="submit" name="action" value="update">Запази продукт</button>
                                    <button class="btn btn-outline-success" type="submit" name="action" value="update_promotions">Запази промоциите</button>
                                    <button class="btn btn-outline-danger" type="submit" name="action" value="delete" onclick="return confirm('Сигурен ли си, че искаш да изтриеш този продукт?');">Изтрий продукт</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php admin_shell_end(); ?>
