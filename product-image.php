<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT name, category FROM products WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit;
}

$name = (string)$product['name'];
$category = (string)$product['category'];

$palette = [
    'Graphics Cards' => ['#0f2744', '#275d97'],
    'Processors' => ['#3d1f0f', '#b66a2a'],
    'Motherboards' => ['#1b2533', '#3f587a'],
    'Memory' => ['#1f3f3a', '#2f8579'],
    'Storage' => ['#222a36', '#5f7088'],
    'Power Supplies' => ['#2c2430', '#7a5a85'],
    'Cases' => ['#1f2a2a', '#5f8585'],
    'Components' => ['#1f3350', '#4d79aa'],
];
$colors = $palette[$category] ?? $palette['Components'];

$title = htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8');
$cat = htmlspecialchars($category, ENT_QUOTES | ENT_XML1, 'UTF-8');
$initial = htmlspecialchars(substr($category, 0, 1), ENT_QUOTES | ENT_XML1, 'UTF-8');

header('Content-Type: image/svg+xml; charset=UTF-8');
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$colors[0]}"/>
      <stop offset="100%" stop-color="{$colors[1]}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="900" fill="url(#g)"/>
  <circle cx="160" cy="160" r="92" fill="rgba(255,255,255,.14)"/>
  <text x="160" y="190" text-anchor="middle" fill="#fff" font-size="120" font-family="Segoe UI, Arial, sans-serif" font-weight="700">{$initial}</text>
  <text x="72" y="720" fill="rgba(255,255,255,.86)" font-size="34" font-family="Segoe UI, Arial, sans-serif">{$cat}</text>
  <text x="72" y="790" fill="#fff" font-size="54" font-family="Segoe UI, Arial, sans-serif" font-weight="700">{$title}</text>
</svg>
SVG;
