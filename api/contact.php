<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$category    = trim($_POST['category'] ?? '');
$orderNumber = trim($_POST['order_number'] ?? '') ?: null;
$subject     = trim($_POST['subject'] ?? '');
$message     = trim($_POST['message'] ?? '');

$validCategories = ['order', 'product', 'warranty', 'preorder', 'general'];

if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !in_array($category, $validCategories)
    || strlen($subject) < 3 || !$message) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields correctly.']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO inquiries (name, email, category, order_number, subject, message)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$name, $email, $category, $orderNumber, $subject, $message]);

echo json_encode(['ok' => true]);
