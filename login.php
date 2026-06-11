<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/branding.php';

redirect_if_logged_in();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Моля, въведете валиден имейл.';
    }
    if ($password === '') {
        $errors[] = 'Паролата е задължителна.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, full_name, email, password_hash, is_admin, is_owner FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Невалиден имейл или парола.';
        } else {
            login_user($user);
            set_flash('success', 'Добре дошли отново.');
            header('Location: Main.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Вход')) ?></title>
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
                    <h1 class="h3 mb-4">Вход</h1>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($flashError = get_flash('error')): ?>
                        <div class="alert alert-warning"><?= htmlspecialchars($flashError) ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="email">Имейл</label>
                            <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password">Парола</label>
                            <input class="form-control" type="password" id="password" name="password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Вход</button>
                    </form>
                    <p class="text-secondary mt-3 mb-0">Нямате профил? <a href="register.php">Създайте акаунт</a></p>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

