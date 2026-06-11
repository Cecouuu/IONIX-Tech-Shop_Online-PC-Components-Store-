<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/product-helpers.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Project/admin.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$returnTo = (string)($_POST['return_to'] ?? 'admin.php');
$allowedReturns = ['admin.php', 'admin-products.php', 'admin-highlights.php'];
if (!in_array($returnTo, $allowedReturns, true)) {
    $returnTo = 'admin.php';
}
$redirectPath = '/Project/' . $returnTo;

function uploads_directory(): string
{
    $dir = dirname(__DIR__, 2) . '/assets/uploads/products';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function is_valid_image_reference(string $imageRef): bool
{
    return filter_var($imageRef, FILTER_VALIDATE_URL) !== false || is_local_product_upload($imageRef);
}

function remove_local_product_image(string $imageRef): void
{
    if (!is_local_product_upload($imageRef)) {
        return;
    }

    $fullPath = dirname(__DIR__, 2) . '/' . ltrim($imageRef, '/');
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function upload_error_message(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файлът е по-голям от позволения размер.',
        UPLOAD_ERR_PARTIAL => 'Файлът е качен непълно.',
        UPLOAD_ERR_NO_FILE => '',
        default => 'Възникна грешка при качването на изображението.',
    };
}

function store_uploaded_product_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message((int)($file['error'] ?? UPLOAD_ERR_NO_FILE)));
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Невалиден качен файл.');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        throw new RuntimeException('Изображението трябва да бъде до 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpName);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Позволени са само JPG, PNG, WEBP и GIF изображения.');
    }

    $baseName = 'product-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $relativePath = 'assets/uploads/products/' . $baseName;
    $targetPath = uploads_directory() . '/' . $baseName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Неуспешно записване на каченото изображение.');
    }

    return $relativePath;
}

function resolve_image_reference(string $fieldName, string $imageUrl, string $currentImageUrl = ''): string
{
    $imageUrl = trim($imageUrl);
    $currentImageUrl = trim($currentImageUrl);
    $file = $_FILES[$fieldName] ?? null;

    if (is_array($file) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        return store_uploaded_product_image($file);
    }

    if ($imageUrl !== '') {
        if (!is_valid_image_reference($imageUrl)) {
            throw new RuntimeException('Невалиден адрес за изображение.');
        }
        return $imageUrl;
    }

    if ($currentImageUrl !== '') {
        return $currentImageUrl;
    }

    throw new RuntimeException('Трябва да зададеш линк към изображение или да качиш снимка.');
}

if ($action === 'create') {
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'Components'));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name === '' || $category === '' || $description === '' || $price <= 0 || $stock < 0) {
        set_flash('error', 'Невалидни данни за новия продукт.');
        header('Location: ' . $redirectPath);
        exit;
    }

    try {
        $imageUrl = resolve_image_reference('image_file', (string)($_POST['image_url'] ?? ''));
    } catch (RuntimeException $e) {
        set_flash('error', $e->getMessage());
        header('Location: ' . $redirectPath);
        exit;
    }

    $stmt = db()->prepare('
        INSERT INTO products (name, category, description, image_url, price, stock)
        VALUES (:name, :category, :description, :image_url, :price, :stock)
    ');
    $stmt->execute([
        'name' => $name,
        'category' => $category,
        'description' => $description,
        'image_url' => $imageUrl,
        'price' => $price,
        'stock' => $stock,
    ]);
    set_flash('success', 'Продуктът е добавен успешно.');
    header('Location: ' . $redirectPath);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'Components'));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $currentImageUrl = trim((string)($_POST['current_image_url'] ?? ''));

    if ($id < 1 || $name === '' || $category === '' || $description === '' || $price <= 0 || $stock < 0) {
        set_flash('error', 'Невалидни данни за редакция на продукта.');
        header('Location: ' . $redirectPath);
        exit;
    }

    try {
        $imageUrl = resolve_image_reference('image_file', (string)($_POST['image_url'] ?? ''), $currentImageUrl);
    } catch (RuntimeException $e) {
        set_flash('error', $e->getMessage());
        header('Location: ' . $redirectPath);
        exit;
    }

    $stmt = db()->prepare('
        UPDATE products
        SET name = :name, category = :category, description = :description, image_url = :image_url, price = :price, stock = :stock
        WHERE id = :id
    ');
    $stmt->execute([
        'id' => $id,
        'name' => $name,
        'category' => $category,
        'description' => $description,
        'image_url' => $imageUrl,
        'price' => $price,
        'stock' => $stock,
    ]);

    if ($currentImageUrl !== '' && $currentImageUrl !== $imageUrl) {
        remove_local_product_image($currentImageUrl);
    }

    set_flash('success', 'Продуктът е обновен успешно.');
    header('Location: ' . $redirectPath);
    exit;
}

if ($action === 'update_promotions') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) {
        set_flash('error', 'Невалиден идентификатор на продукт.');
        header('Location: ' . $redirectPath);
        exit;
    }

    $weeklyDiscount = (float)($_POST['weekly_discount_percent'] ?? 0);
    $weeklyLabel = trim((string)($_POST['weekly_label'] ?? ''));
    $weeklyActive = isset($_POST['weekly_active']) ? 1 : 0;

    $weekendDiscount = (float)($_POST['weekend_discount_percent'] ?? 0);
    $weekendLabel = trim((string)($_POST['weekend_label'] ?? ''));
    $weekendActive = isset($_POST['weekend_active']) ? 1 : 0;

    $values = [$weeklyDiscount, $weekendDiscount];
    foreach ($values as $value) {
        if ($value < 0 || $value > 90) {
            set_flash('error', 'Намалението трябва да е между 0% и 90%.');
            header('Location: ' . $redirectPath);
            exit;
        }
    }

    $upsert = db()->prepare('
        INSERT INTO product_highlights (product_id, highlight_type, discount_percent, label, is_active)
        VALUES (:product_id, :highlight_type, :discount_percent, :label, :is_active)
        ON DUPLICATE KEY UPDATE
          discount_percent = VALUES(discount_percent),
          label = VALUES(label),
          is_active = VALUES(is_active)
    ');

    $upsert->execute([
        'product_id' => $id,
        'highlight_type' => 'weekly',
        'discount_percent' => $weeklyDiscount,
        'label' => $weeklyLabel,
        'is_active' => $weeklyActive,
    ]);
    $upsert->execute([
        'product_id' => $id,
        'highlight_type' => 'weekend',
        'discount_percent' => $weekendDiscount,
        'label' => $weekendLabel,
        'is_active' => $weekendActive,
    ]);

    set_flash('success', 'Промоциите са обновени успешно.');
    header('Location: ' . $redirectPath);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $currentImageUrl = trim((string)($_POST['current_image_url'] ?? ''));
    if ($id < 1) {
        set_flash('error', 'Невалиден идентификатор на продукт.');
        header('Location: ' . $redirectPath);
        exit;
    }

    $check = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :id');
    $check->execute(['id' => $id]);
    $usedCount = (int)$check->fetchColumn();
    if ($usedCount > 0) {
        set_flash('error', 'Продукт с история на покупки не може да бъде изтрит.');
        header('Location: ' . $redirectPath);
        exit;
    }

    $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    remove_local_product_image($currentImageUrl);
    set_flash('success', 'Продуктът е изтрит.');
    header('Location: ' . $redirectPath);
    exit;
}

set_flash('error', 'Невалидно действие за продукт.');
header('Location: ' . $redirectPath);
exit;

