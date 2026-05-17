<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Please sign in to place an order.', 'redirect' => 'signin.php']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

$body  = json_decode(file_get_contents('php://input'), true);
$items = $body['items'] ?? [];

if (!$items || !is_array($items)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Your cart is empty.']);
    exit;
}

// Calculate total from submitted prices
$total = 0;
foreach ($items as $item) {
    $total += (float) ($item['price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
}

// Generate a unique order number
$orderNumber = 'GB-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, status)
        VALUES (?, ?, ?, 'paid')
    ");
    $stmt->execute([$_SESSION['user_id'], $orderNumber, $total]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $itemStmt->execute([
            $orderId,
            (int) ($item['productId'] ?? 0) ?: null,
            htmlspecialchars(strip_tags($item['name'] ?? 'Product')),
            (float) ($item['price'] ?? 0),
            max(1, (int) ($item['quantity'] ?? 1)),
        ]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'order_number' => $orderNumber, 'total' => $total]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[GameBlitz Checkout] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Order could not be placed. Please try again.']);
}
