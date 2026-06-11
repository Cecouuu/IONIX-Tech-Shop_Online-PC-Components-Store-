<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/branding.php';

redirect_if_logged_in();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Името трябва да е поне 2 символа.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Моля, въведете валиден имейл.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Паролата трябва да е поне 6 символа.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Паролите не съвпадат.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Този имейл вече съществува.';
        }
    }

    if (!$errors) {
        $insert = db()->prepare('INSERT INTO users (full_name, email, password_hash, is_admin, is_owner) VALUES (:full_name, :email, :password_hash, 0, 0)');
        $insert->execute([
            'full_name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $userId = (int)db()->lastInsertId();
        login_user(['id' => $userId, 'full_name' => $name, 'email' => $email, 'is_admin' => 0, 'is_owner' => 0]);
        set_flash('success', 'Регистрацията е успешна.');
        header('Location: Main.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Регистрация')) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h3 mb-4">Създай акаунт</h1>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="full_name">Име и фамилия</label>
                            <input class="form-control" type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Имейл</label>
                            <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Парола</label>
                            <input class="form-control" type="password" id="password" name="password" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="confirm_password">Потвърди парола</label>
                            <input class="form-control" type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Регистрация</button>
                    </form>
                    <p class="text-secondary mt-3 mb-0">Вече имате акаунт? <a href="login.php">Вход</a></p>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

