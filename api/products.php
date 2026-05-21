<?php
// AJAX product search/filter endpoint — returns JSON array of products
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../database/db.php';

$q        = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$platform = trim($_GET['platform'] ?? '');
$sort     = trim($_GET['sort']     ?? 'featured');

$allowedSorts = ['featured', 'price-asc', 'price-desc', 'name-asc'];
if (!in_array($sort, $allowedSorts)) {
    $sort = 'featured';
}

$params = [];
$where  = ['p.is_active = 1'];

if ($q !== '') {
    $where[]  = 'p.name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($category !== '') {
    $where[]  = 'c.slug = ?';
    $params[] = $category;
}
if ($platform !== '') {
    $where[]  = 'EXISTS (
        SELECT 1 FROM product_platforms pp2
        JOIN platforms pl2 ON pl2.platform_id = pp2.platform_id
        WHERE pp2.product_id = p.product_id AND pl2.slug = ?
    )';
    $params[] = $platform;
}

$orderBy = match ($sort) {
    'price-asc'  => 'p.price ASC',
    'price-desc' => 'p.price DESC',
    'name-asc'   => 'p.name ASC',
    default      => 'p.is_featured DESC, p.created_at DESC',
};

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT  p.product_id,
            p.name,
            p.price,
            p.sale_price,
            p.image_url,
            p.stock,
            p.badge,
            p.description,
            c.slug  AS category_slug,
            GROUP_CONCAT(pl.slug ORDER BY pl.slug SEPARATOR ',') AS platforms
    FROM    products p
    JOIN    categories c ON c.category_id = p.category_id
    LEFT JOIN product_platforms pp ON pp.product_id = p.product_id
    LEFT JOIN platforms pl         ON pl.platform_id = pp.platform_id
    WHERE   $whereSQL
    GROUP   BY p.product_id
    ORDER   BY $orderBy
    LIMIT   60
");
$stmt->execute($params);
$products = $stmt->fetchAll();

echo json_encode(['ok' => true, 'products' => $products, 'count' => count($products)]);
