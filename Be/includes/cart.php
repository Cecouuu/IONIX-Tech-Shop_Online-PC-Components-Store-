<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }

    $clean = [];
    foreach ($cart as $productId => $quantity) {
        $pid = (int)$productId;
        $qty = (int)$quantity;
        if ($pid > 0 && $qty > 0) {
            $clean[$pid] = $qty;
        }
    }

    return $clean;
}

function cart_count(): int
{
    return array_sum(cart_items());
}

function cart_add(int $productId, int $quantity): void
{
    $cart = cart_items();
    $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
    $_SESSION['cart'] = $cart;
}

function cart_update(int $productId, int $quantity): void
{
    $cart = cart_items();
    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }
    $_SESSION['cart'] = $cart;
}

function cart_remove(int $productId): void
{
    $cart = cart_items();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

