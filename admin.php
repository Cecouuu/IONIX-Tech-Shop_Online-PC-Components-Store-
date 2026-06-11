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

$totalUsers = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalOrders = (int)db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalLogins = (int)db()->query('SELECT COUNT(*) FROM user_logins')->fetchColumn();
$totalProducts = (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn();

$dailyEarnings = (float)db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$monthlyEarnings = (float)db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())')->fetchColumn();
$yearlyEarnings = (float)db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE YEAR(created_at) = YEAR(CURDATE())')->fetchColumn();

$recentOrders = db()->query('
    SELECT
      o.id,
      o.total_amount,
      o.created_at,
      COALESCE(u.full_name, o.guest_name) AS buyer_name,
      COALESCE(u.email, o.guest_email) AS buyer_email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
    LIMIT 8
')->fetchAll();

admin_shell_start('Админ табло', 'dashboard');
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h4 mb-0">Админ табло</h2>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Потребители</p><h3 class="h4 mb-0"><?= $totalUsers ?></h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Продукти</p><h3 class="h4 mb-0"><?= $totalProducts ?></h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Поръчки</p><h3 class="h4 mb-0"><?= $totalOrders ?></h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Влизания</p><h3 class="h4 mb-0"><?= $totalLogins ?></h3></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Дневни приходи</p><h3 class="h4 mb-0"><?= number_format($dailyEarnings, 2) ?> лв.</h3></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Месечни приходи</p><h3 class="h4 mb-0"><?= number_format($monthlyEarnings, 2) ?> лв.</h3></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Годишни приходи</p><h3 class="h4 mb-0"><?= number_format($yearlyEarnings, 2) ?> лв.</h3></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h5 mb-3">Последни поръчки</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>Поръчка №</th>
                    <th>Купувач</th>
                    <th>Имейл</th>
                    <th>Общо</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?= (int)$order['id'] ?></td>
                        <td><?= htmlspecialchars((string)$order['buyer_name']) ?></td>
                        <td><?= htmlspecialchars(is_owner() ? (string)$order['buyer_email'] : $maskEmail((string)$order['buyer_email'])) ?></td>
                        <td><?= number_format((float)$order['total_amount'], 2) ?> лв.</td>
                        <td><?= htmlspecialchars($order['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_shell_end(); ?>

