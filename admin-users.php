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

$users = db()->query('
    SELECT id, full_name, email, is_admin, is_owner, created_at
    FROM users
    ORDER BY id DESC
')->fetchAll();

if (is_owner()) {
    $logins = db()->query('
        SELECT ul.logged_in_at, ul.ip_address, ul.user_agent, u.full_name, u.email
        FROM user_logins ul
        INNER JOIN users u ON u.id = ul.user_id
        ORDER BY ul.id DESC
        LIMIT 200
    ')->fetchAll();
} else {
    $logins = db()->query('
        SELECT ul.logged_in_at, NULL AS ip_address, NULL AS user_agent, u.full_name, u.email
        FROM user_logins ul
        INNER JOIN users u ON u.id = ul.user_id
        ORDER BY ul.id DESC
        LIMIT 200
    ')->fetchAll();
}

admin_shell_start('Админ потребители', 'users');
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h4 mb-3">Потребители</h2>
        <p class="small text-secondary mb-3">Чувствителни данни за достъп никога не се показват в интерфейса.</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Име</th>
                    <th>Имейл</th>
                    <th>Роля</th>
                    <th>Създаден на</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int)$user['id'] ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars(is_owner() ? $user['email'] : $maskEmail((string)$user['email'])) ?></td>
                        <td>
                            <?php if ((int)$user['is_owner'] === 1): ?>
                                Собственик
                            <?php elseif ((int)$user['is_admin'] === 1): ?>
                                Админ
                            <?php else: ?>
                                Потребител
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h5 mb-3">История на влизанията</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>Дата/час</th>
                    <th>Име</th>
                    <th>Имейл</th>
                    <?php if (is_owner()): ?>
                        <th>IP</th>
                        <th>Потребителски агент</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logins as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['logged_in_at']) ?></td>
                        <td><?= htmlspecialchars($log['full_name']) ?></td>
                        <td><?= htmlspecialchars(is_owner() ? $log['email'] : $maskEmail((string)$log['email'])) ?></td>
                        <?php if (is_owner()): ?>
                            <td><?= htmlspecialchars((string)$log['ip_address']) ?></td>
                            <td class="small"><?= htmlspecialchars((string)$log['user_agent']) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_shell_end(); ?>

