<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';
require_once __DIR__ . '/Be/includes/branding.php';

require_login();

$user = current_user();
$errors = [];
$title = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($title === '' || mb_strlen($title) < 3) {
        $errors[] = 'Заглавието трябва да е поне 3 символа.';
    }
    if ($message === '' || mb_strlen($message) < 10) {
        $errors[] = 'Съобщението трябва да е поне 10 символа.';
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO user_requests (user_id, title, message) VALUES (:user_id, :title, :message)');
        $stmt->execute([
            'user_id' => (int)$user['id'],
            'title' => $title,
            'message' => $message,
        ]);

        set_flash('success', 'Заявката е изпратена успешно.');
        header('Location: my-requests.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(site_title('Нова заявка')) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/Main.css">
</head>
<body class="bg-soft">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0">Нова заявка</h1>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="products.php">Продукти</a>
                    <a class="btn btn-outline-secondary" href="my-requests.php">Моите заявки</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
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
                            <label class="form-label" for="title">Заглавие</label>
                            <input class="form-control" type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="message">Съобщение</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?= htmlspecialchars($message) ?></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Изпрати заявка</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

