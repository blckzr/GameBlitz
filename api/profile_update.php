<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

$userId   = (int) $_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email']     ?? '');
$password = $_POST['password']         ?? '';
$confirm  = $_POST['password_confirm'] ?? '';

$errors = [];

if (strlen($fullName) < 2) {
    $errors['full_name'] = 'Full name must be at least 2 characters.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}

if (!isset($errors['email'])) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $check->execute([$email, $userId]);
    if ((int) $check->fetchColumn() > 0) {
        $errors['email'] = 'This email is already in use by another account.';
    }
}

if ($password !== '') {
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE user_id = ?");
    $stmt->execute([$fullName, $email, $hash, $userId]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
    $stmt->execute([$fullName, $email, $userId]);
}

$_SESSION['name']  = $fullName;
$_SESSION['email'] = $email;

echo json_encode(['ok' => true, 'name' => $fullName, 'email' => $email]);
