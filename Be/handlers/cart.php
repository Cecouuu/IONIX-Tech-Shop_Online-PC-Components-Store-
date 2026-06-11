<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

$action = (string)($_POST['action'] ?? '');

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = db()->prepare('SELECT id, stock FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('error', 'Продуктът не е намерен.');
        header('Location: /Project/products.php');
        exit;
    }

    if ((int)$product['stock'] < 1) {
        set_flash('error', 'Продуктът не е наличен.');
        header('Location: /Project/products.php');
        exit;
    }

    $quantity = min($quantity, (int)$product['stock']);
    cart_add($productId, $quantity);
    set_flash('success', 'Продуктът е добавен в количката.');
    header('Location: /Project/products.php');
    exit;
}

if ($action === 'update') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    $stmt = db()->prepare('SELECT id, stock FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('error', 'Продуктът не е намерен.');
        header('Location: /Project/cart.php');
        exit;
    }

    if ($quantity > (int)$product['stock']) {
        $quantity = (int)$product['stock'];
    }

    cart_update($productId, $quantity);
    set_flash('success', 'Количката е обновена.');
    header('Location: /Project/cart.php');
    exit;
}

if ($action === 'remove') {
    $productId = (int)($_POST['product_id'] ?? 0);
    cart_remove($productId);
    set_flash('success', 'Продуктът е премахнат от количката.');
    header('Location: /Project/cart.php');
    exit;
}

if ($action === 'clear') {
    cart_clear();
    set_flash('success', 'Количката е изчистена.');
    header('Location: /Project/cart.php');
    exit;
}

if ($action === 'checkout') {
    $user = current_user();
    $cart = cart_items();
    $buyerName = trim((string)($_POST['buyer_name'] ?? ''));
    $buyerEmail = trim((string)($_POST['buyer_email'] ?? ''));
    $buyerPhone = trim((string)($_POST['buyer_phone'] ?? ''));
    $buyerAddress = trim((string)($_POST['buyer_address'] ?? ''));

    if (!$cart) {
        set_flash('error', 'Количката е празна.');
        header('Location: /Project/cart.php');
        exit;
    }

    if ($buyerName === '' || mb_strlen($buyerName) < 2) {
        set_flash('error', 'Моля, въведете валидни имена.');
        header('Location: /Project/cart.php');
        exit;
    }
    if (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Моля, въведете валиден имейл адрес.');
        header('Location: /Project/cart.php');
        exit;
    }
    if ($buyerPhone === '' || mb_strlen($buyerPhone) < 6) {
        set_flash('error', 'Моля, въведете валиден телефонен номер.');
        header('Location: /Project/cart.php');
        exit;
    }
    if ($buyerAddress === '' || mb_strlen($buyerAddress) < 8) {
        set_flash('error', 'Моля, въведете валиден адрес за доставка.');
        header('Location: /Project/cart.php');
        exit;
    }

    try {
        db()->beginTransaction();

        $orderTotal = 0.0;
        $products = [];
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $productStmt = db()->prepare("SELECT id, name, price, stock FROM products WHERE id IN ({$placeholders}) FOR UPDATE");
        $productStmt->execute($ids);
        foreach ($productStmt->fetchAll() as $p) {
            $products[(int)$p['id']] = $p;
        }

        foreach ($cart as $pid => $qty) {
            if (!isset($products[$pid])) {
                throw new RuntimeException('Някой от продуктите в количката вече не съществува.');
            }
            if ((int)$products[$pid]['stock'] < $qty) {
                throw new RuntimeException('Недостатъчна наличност за ' . $products[$pid]['name'] . '.');
            }
            $orderTotal += ((float)$products[$pid]['price'] * $qty);
        }

        $orderStmt = db()->prepare('
            INSERT INTO orders (user_id, guest_name, guest_email, guest_phone, guest_address, total_amount)
            VALUES (:user_id, :guest_name, :guest_email, :guest_phone, :guest_address, :total_amount)
        ');
        $orderStmt->execute([
            'user_id' => $user ? (int)$user['id'] : null,
            'guest_name' => $buyerName,
            'guest_email' => $buyerEmail,
            'guest_phone' => $buyerPhone,
            'guest_address' => $buyerAddress,
            'total_amount' => $orderTotal,
        ]);
        $orderId = (int)db()->lastInsertId();

        $itemStmt = db()->prepare('
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (:order_id, :product_id, :quantity, :unit_price)
        ');
        $stockStmt = db()->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');

        foreach ($cart as $pid => $qty) {
            $price = (float)$products[$pid]['price'];
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $pid,
                'quantity' => $qty,
                'unit_price' => $price,
            ]);
            $stockStmt->execute([
                'qty' => $qty,
                'id' => $pid,
            ]);
        }

        db()->commit();
        cart_clear();
        set_flash('success', 'Поръчката беше завършена успешно.');
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        set_flash('error', $e->getMessage());
    }

    if ($user) {
        header('Location: /Project/my-orders.php');
    } else {
        header('Location: /Project/products.php');
    }
    exit;
}

set_flash('error', 'Невалидно действие за количката.');
header('Location: /Project/products.php');
exit;

