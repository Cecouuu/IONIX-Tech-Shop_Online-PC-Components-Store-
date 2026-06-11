<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/admin-shell.php';

require_admin();

$maskEmail = static function (string $email): string {
    if (strpos($email, '@') === false) {
        return 'hidden';
    }
    [$namePart, $domainPart] = explode('@', $email, 2);
    $visible = substr($namePart, 0, 2);
    return $visible . str_repeat('*', max(1, strlen($namePart) - 2)) . '@' . $domainPart;
};

if (is_owner()) {
    $purchases = db()->query('
        SELECT
            o.id AS order_id,
            o.created_at,
            COALESCE(u.full_name, o.guest_name) AS buyer_name,
            COALESCE(u.email, o.guest_email) AS buyer_email,
            o.guest_phone,
            o.guest_address,
            p.name AS product_name,
            oi.quantity,
            oi.unit_price,
            (oi.quantity * oi.unit_price) AS line_total
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        LEFT JOIN users u ON u.id = o.user_id
        INNER JOIN products p ON p.id = oi.product_id
        ORDER BY oi.id DESC
        LIMIT 300
    ')->fetchAll();
} else {
    $purchases = db()->query('
        SELECT
            o.id AS order_id,
            o.created_at,
            COALESCE(u.full_name, o.guest_name) AS buyer_name,
            COALESCE(u.email, o.guest_email) AS buyer_email,
            NULL AS guest_phone,
            NULL AS guest_address,
            p.name AS product_name,
            oi.quantity,
            oi.unit_price,
            (oi.quantity * oi.unit_price) AS line_total
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        LEFT JOIN users u ON u.id = o.user_id
        INNER JOIN products p ON p.id = oi.product_id
        ORDER BY oi.id DESC
        LIMIT 300
    ')->fetchAll();
}

admin_shell_start('Админ поръчки', 'orders');
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h4 mb-3">История на покупките</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>Поръчка №</th>
                    <th>Дата</th>
                    <th>Купувач</th>
                    <th>Имейл</th>
                    <?php if (is_owner()): ?>
                        <th>Телефон</th>
                        <th>Адрес</th>
                    <?php endif; ?>
                    <th>Продукт</th>
                    <th>Брой</th>
                    <th>Ед. цена</th>
                    <th>Общо</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?= (int)$purchase['order_id'] ?></td>
                            <td><?= htmlspecialchars($purchase['created_at']) ?></td>
                            <td><?= htmlspecialchars((string)$purchase['buyer_name']) ?></td>
                            <td><?= htmlspecialchars(is_owner() ? (string)$purchase['buyer_email'] : $maskEmail((string)$purchase['buyer_email'])) ?></td>
                            <?php if (is_owner()): ?>
                                <td><?= htmlspecialchars((string)$purchase['guest_phone']) ?></td>
                                <td><?= htmlspecialchars((string)$purchase['guest_address']) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($purchase['product_name']) ?></td>
                            <td><?= (int)$purchase['quantity'] ?></td>
                            <td><?= number_format((float)$purchase['unit_price'], 2) ?> лв.</td>
                        <td><?= number_format((float)$purchase['line_total'], 2) ?> лв.</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_shell_end(); ?>

