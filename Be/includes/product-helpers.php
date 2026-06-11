<?php
declare(strict_types=1);

function bg_category(string $category): string
{
    static $map = [
        'All' => 'Всички',
        'Graphics Cards' => 'Видео карти',
        'Processors' => 'Процесори',
        'Motherboards' => 'Дънни платки',
        'Memory' => 'Памет',
        'Storage' => 'Съхранение',
        'Power Supplies' => 'Захранвания',
        'Cases' => 'Кутии',
        'Components' => 'Компоненти',
    ];

    return $map[$category] ?? $category;
}

function normalize_product_row(array $product): array
{
    return array_merge($product, [
        'id' => (int)($product['id'] ?? 0),
        'name' => trim((string)($product['name'] ?? '')),
        'category' => trim((string)($product['category'] ?? '')) ?: 'Components',
        'description' => trim((string)($product['description'] ?? '')),
        'image_url' => trim((string)($product['image_url'] ?? '')),
        'price' => (float)($product['price'] ?? 0),
        'stock' => max(0, (int)($product['stock'] ?? 0)),
        'label' => trim((string)($product['label'] ?? '')),
        'discount_percent' => (float)($product['discount_percent'] ?? 0),
    ]);
}

function product_image_src(array $product): string
{
    $imageUrl = trim((string)($product['image_url'] ?? ''));
    return $imageUrl !== '' ? $imageUrl : 'product-image.php?id=' . (int)($product['id'] ?? 0);
}

function stock_status_info(int $stock): array
{
    if ($stock <= 0) {
        return [
            'label' => 'Изчерпан',
            'class' => 'bg-danger-subtle text-danger-emphasis',
            'description' => 'Няма наличност в момента.',
        ];
    }

    if ($stock <= 5) {
        return [
            'label' => 'Малко бройки',
            'class' => 'bg-warning-subtle text-warning-emphasis',
            'description' => 'Остават ограничени количества.',
        ];
    }

    return [
        'label' => 'В наличност',
        'class' => 'bg-success-subtle text-success-emphasis',
        'description' => 'Продуктът е наличен за поръчка.',
    ];
}

function is_local_product_upload(string $path): bool
{
    return str_starts_with(str_replace('\\', '/', $path), 'assets/uploads/products/');
}
