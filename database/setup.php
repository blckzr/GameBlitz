<?php
/**
 * One-time admin password setup.
 * Run this once via XAMPP (http://localhost/COMP-016---WebDev/database/setup.php),
 * then DELETE this file immediately.
 *
 * Default credentials set by this script:
 *   Email:    admin@gameblitz.com
 *   Password: Admin@GameBlitz1
 *
 * Change the password below before running if you want a different one.
 */

require_once __DIR__ . '/db.php';

$plainPassword = 'Admin@GameBlitz1'; // <-- change this if desired
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@gameblitz.com'");
$stmt->execute([$hash]);

if ($stmt->rowCount() === 1) {
    echo '<p style="font-family:sans-serif;color:green;padding:40px">';
    echo '&#10003; Admin password set successfully.<br>';
    echo '<strong>Delete this file (database/setup.php) now!</strong>';
    echo '</p>';
} else {
    echo '<p style="font-family:sans-serif;color:red;padding:40px">';
    echo '&#9888; No admin account found. Make sure you imported gameblitz.sql first.';
    echo '</p>';
}
