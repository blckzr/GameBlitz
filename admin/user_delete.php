<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$targetId = (int) ($_POST['user_id'] ?? 0);
$action   = $_POST['action'] ?? 'deactivate';

// Prevent admin from acting on their own account
if (!$targetId || $targetId === (int) $_SESSION['user_id']) {
    header('Location: users.php?flash=error');
    exit;
}

$isActive = ($action === 'activate') ? 1 : 0;
$stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
$stmt->execute([$isActive, $targetId]);

header('Location: users.php?flash=' . ($isActive ? 'saved' : 'deleted'));
exit;
