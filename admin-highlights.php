<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/admin-shell.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $type = (string)($_POST['highlight_type'] ?? '');
        $discount = (float)($_POST['discount_percent'] ?? 0);
        $label = trim((string)($_POST['label'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($productId < 1 || !in_array($type, ['weekly', 'weekend'], true) || $discount < 0 || $discount > 90) {
            set_flash('error', 'Невалидни данни за промоцията.');
            header('Location: admin-highlights.php');
            exit;
        }

        $stmt = db()->prepare('
            INSERT INTO product_highlights (product_id, highlight_type, discount_percent, label, is_active)
            VALUES (:product_id, :highlight_type, :discount_percent, :label, :is_active)
            ON DUPLICATE KEY UPDATE
              discount_percent = VALUES(discount_percent),
              label = VALUES(label),
              is_active = VALUES(is_active)
        ');
        $stmt->execute([
            'product_id' => $productId,
            'highlight_type' => $type,
            'discount_percent' => $discount,
            'label' => $label,
            'is_active' => $isActive,
        ]);
        set_flash('success', 'Промоцията е запазена.');
        header('Location: admin-highlights.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('DELETE FROM product_highlights WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Промоцията е премахната.');
        }
        header('Location: admin-highlights.php');
        exit;
    }
}

$products = db()->query('SELECT id, name FROM products ORDER BY name ASC')->fetchAll();
$highlights = db()->query('
    SELECT h.id, h.product_id, h.highlight_type, h.discount_percent, h.label, h.is_active, p.name AS product_name
    FROM product_highlights h
    INNER JOIN products p ON p.id = h.product_id
    ORDER BY h.created_at DESC
')->fetchAll();

admin_shell_start('Админ промоции', 'highlights');
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h4 mb-3">Промоции за началната страница</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="action" value="save">
            <div class="col-md-4">
                <select class="form-select" name="product_id" required>
                    <option value="">Избери продукт</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="highlight_type" required>
                    <option value="weekly">Продукти на седмицата</option>
                    <option value="weekend">Уикенд намаление</option>
                </select>
            </div>
            <div class="col-md-2">
                <input class="form-control" type="number" step="0.01" min="0" max="90" name="discount_percent" placeholder="Намаление %" value="0" required>
            </div>
            <div class="col-md-3">
                <input class="form-control" type="text" name="label" placeholder="Етикет (по желание)">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary" type="submit">Запази</button>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                    <label class="form-check-label" for="is_active">Активна</label>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h5 mb-3">Текущи промоции</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>Продукт</th>
                    <th>Тип</th>
                    <th>Намаление %</th>
                    <th>Етикет</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($highlights as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['product_name']) ?></td>
                        <td><?= htmlspecialchars($h['highlight_type'] === 'weekly' ? 'седмична' : 'уикенд') ?></td>
                        <td><?= number_format((float)$h['discount_percent'], 2) ?>%</td>
                        <td><?= htmlspecialchars((string)$h['label']) ?></td>
                        <td><?= (int)$h['is_active'] === 1 ? 'Активна' : 'Неактивна' ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Сигурен ли си, че искаш да изтриеш тази промоция?');">Изтрий</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_shell_end(); ?>
