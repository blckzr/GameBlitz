<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

$token    = trim($_POST['token']            ?? '');
$password = $_POST['password']               ?? '';
$confirm  = $_POST['password_confirm']       ?? '';

// Token format check
if (!$token || !ctype_xdigit($token) || strlen($token) !== 64) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid reset token.']);
    exit;
}

$errors = [];
if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}
if ($password !== $confirm) {
    $errors['password_confirm'] = 'Passwords do not match.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// Look up + validate token
$stmt = $pdo->prepare("
    SELECT user_id, expires_at
    FROM   password_resets
    WHERE  token = ?
    LIMIT  1
");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'This reset link is invalid or has already been used.']);
    exit;
}

if (strtotime($row['expires_at']) < time()) {
    $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'This reset link has expired. Please request a new one.']);
    exit;
}

// Update password + invalidate token in a transaction
$pdo->beginTransaction();
try {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")
        ->execute([$hash, $row['user_id']]);

    $pdo->prepare("DELETE FROM password_resets WHERE token = ?")
        ->execute([$token]);

    // Also clear any other unused tokens for this user
    $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")
        ->execute([$row['user_id']]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Password updated. You can now sign in.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[GameBlitz Reset] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not update password. Please try again.']);
}
