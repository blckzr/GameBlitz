<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$id = (int) ($_POST['product_id'] ?? 0);
if (!$id) {
    header('Location: products.php?flash=error');
    exit;
}

// Verify product exists
$stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ? LIMIT 1");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    header('Location: products.php?flash=error');
    exit;
}

// Hard delete — FK cascade removes product_platforms rows automatically;
// order_items.product_id is SET NULL so order history is preserved.
$pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$id]);

header('Location: products.php?flash=deleted');
exit;
