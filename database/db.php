<?php
/**
 * GameBlitz — Database connection (PDO)
 *
 * Include this file at the top of every PHP page that needs DB access:
 *   require_once __DIR__ . '/../database/db.php';
 *
 * PHP phase checklist (Activity 4 rubric):
 *   ✔ Config separated from logic (db.php)
 *   ✔ PDO with prepared statements (prevents SQL injection)
 *   ✔ Exception-based error handling (no credentials leaked on error)
 *   ✔ utf8mb4 charset enforced at connection level
 */

// ── Credentials ───────────────────────────────────────────────────────────
// Move these to a .env file or php dotenv before going live.
// Never commit real credentials to version control.
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'gameblitz_db');
define('DB_USER', 'root');        // change to a dedicated MySQL user
define('DB_PASS', '');            // change to a strong password

// ── Connection ────────────────────────────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared stmts
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the real error server-side; show a safe message to the user.
    error_log('[GameBlitz DB] Connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('<p style="font-family:sans-serif;color:#ff5555;padding:40px">
            Database unavailable. Please try again later.
         </p>');
}
