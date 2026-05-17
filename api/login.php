<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

if (!$email || !$password) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Email and password are required.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT user_id, full_name, email, password, role, is_active
     FROM   users WHERE email = ? LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid email or password.']);
    exit;
}

if (!$user['is_active']) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'This account has been deactivated.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['name']    = $user['full_name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

if ($remember) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), session_id(),
        time() + 60 * 60 * 24 * 30,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}

echo json_encode([
    'ok'       => true,
    'name'     => $user['full_name'],
    'is_admin' => $user['role'] === 'admin',
]);
