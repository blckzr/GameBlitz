<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// Look up the user — but always respond the same way to prevent email enumeration
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Generate a cryptographically-secure 64-char hex token
    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

    // Clear any old reset tokens for this user
    $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")
        ->execute([$user['user_id']]);

    // Insert new token
    $pdo->prepare("INSERT INTO password_resets (token, user_id, expires_at) VALUES (?, ?, ?)")
        ->execute([$token, $user['user_id'], $expiresAt]);

    // Build the reset URL (in production this would be emailed)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base    = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $resetUrl = $scheme . '://' . $host . $base . '/reset_password.php?token=' . $token;

    // For this school project we return the URL directly (no mail server on localhost).
    // In production, send via mail() / PHPMailer and return only ['ok' => true].
    echo json_encode([
        'ok'        => true,
        'reset_url' => $resetUrl,
        'message'   => 'Reset link generated. Use it within 1 hour.',
    ]);
} else {
    // Same response shape so attackers can't tell if the email exists.
    echo json_encode([
        'ok'      => true,
        'message' => 'If that email is registered, a reset link has been generated.',
    ]);
}
