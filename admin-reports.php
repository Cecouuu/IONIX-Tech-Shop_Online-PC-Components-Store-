<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/admin-shell.php';

require_admin();

$dailyEarnings = (float)db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$monthlyEarnings = (float)db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())')->fetchColumn();
$yearlyEarnings = (float)db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE YEAR(created_at) = YEAR(CURDATE())')->fetchColumn();

$monthlyRows = db()->query('
    SELECT DATE_FORMAT(created_at, "%Y-%m") AS ym, SUM(total_amount) AS total
    FROM orders
    GROUP BY DATE_FORMAT(created_at, "%Y-%m")
    ORDER BY ym DESC
    LIMIT 12
')->fetchAll();

$dailyRows = db()->query('
    SELECT DATE(created_at) AS d, SUM(total_amount) AS total
    FROM orders
    GROUP BY DATE(created_at)
    ORDER BY d DESC
    LIMIT 14
')->fetchAll();

admin_shell_start('Админ справки', 'reports');
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Дневни приходи</p><h3 class="h4 mb-0"><?= number_format($dailyEarnings, 2) ?> лв.</h3></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Месечни приходи</p><h3 class="h4 mb-0"><?= number_format($monthlyEarnings, 2) ?> лв.</h3></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-secondary mb-1">Годишни приходи</p><h3 class="h4 mb-0"><?= number_format($yearlyEarnings, 2) ?> лв.</h3></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Последни 14 дни</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Дата</th><th>Общо</th></tr></thead>
                        <tbody>
                        <?php foreach ($dailyRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['d']) ?></td>
                                <td><?= number_format((float)$row['total'], 2) ?> лв.</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Последни 12 месеца</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Месец</th><th>Общо</th></tr></thead>
                        <tbody>
                        <?php foreach ($monthlyRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['ym']) ?></td>
                                <td><?= number_format((float)$row['total'], 2) ?> лв.</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php admin_shell_end(); ?>

