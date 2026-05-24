<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/database/db.php';

$token   = trim($_GET['token'] ?? '');
$isValid = false;
$reason  = '';

if (!$token || !ctype_xdigit($token) || strlen($token) !== 64) {
    $reason = 'Invalid reset link.';
} else {
    $stmt = $pdo->prepare("
        SELECT pr.user_id, pr.expires_at, u.email
        FROM   password_resets pr
        JOIN   users u ON u.user_id = pr.user_id
        WHERE  pr.token = ?
        LIMIT  1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        $reason = 'This reset link is invalid or has already been used.';
    } elseif (strtotime($row['expires_at']) < time()) {
        $reason = 'This reset link has expired. Please request a new one.';
    } else {
        $isValid    = true;
        $userEmail  = $row['email'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password | GameBlitz</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/auth.css" />
</head>
<body>
  <header class="main-header">
    <div class="nav-container">
      <a href="index.php" class="logo">Game<span>Blitz</span></a>
      <button id="mobileMenuToggle" class="mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <nav id="primaryNav">
        <ul>
          <li><a href="index.php" class="nav-btn">Home</a></li>
          <li><a href="products.php" class="nav-btn">Products</a></li>
          <li><a href="contact.php" class="nav-btn">Contact</a></li>
        </ul>
      </nav>
      <div class="nav-actions">
        <a href="signin.php" class="nav-action" aria-label="Sign in">
          <span class="nav-action-icon">&#128100;</span>
          <span class="nav-action-label">Sign In</span>
        </a>
        <a href="cart.html" class="nav-action cart-link" aria-label="View shopping cart">
          <span class="nav-action-icon">&#128722;</span>
          <span class="nav-action-label">Cart</span>
          <span class="cart-badge" id="cartCount">0</span>
        </a>
      </div>
    </div>
  </header>

  <main class="auth-main">
    <div class="auth-card">
      <div class="auth-header">
        <p class="auth-logo">Game<span>Blitz</span></p>
        <h1>Reset your password</h1>

        <?php if ($isValid): ?>
        <p class="auth-subtitle">Setting a new password for <strong><?= htmlspecialchars($userEmail) ?></strong></p>
        <?php else: ?>
        <p class="auth-subtitle" style="color:#f87171"><?= htmlspecialchars($reason) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($isValid): ?>
      <form id="resetForm" class="auth-form" novalidate>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />

        <div class="input-group">
          <label for="password">New Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" />
          </div>
          <span id="passwordError" class="error-msg" aria-live="polite"></span>
        </div>

        <div class="input-group">
          <label for="confirmPassword">Confirm New Password</label>
          <div class="password-wrap">
            <input type="password" id="confirmPassword" name="password_confirm" autocomplete="new-password" placeholder="Repeat your password" />
          </div>
          <span id="confirmError" class="error-msg" aria-live="polite"></span>
        </div>

        <button type="submit" class="submit-btn" id="resetBtn">Update Password</button>
      </form>
      <?php else: ?>
      <a href="forgot_password.php" class="submit-btn" style="display:block;text-align:center;text-decoration:none;margin-top:1rem">Request a new link</a>
      <?php endif; ?>

      <p class="auth-switch">
        Remembered it? <a href="signin.php">Back to sign in &rsaquo;</a>
      </p>
    </div>
  </main>

  <script src="js/cart.js"></script>
  <?php if ($isValid): ?>
  <script src="js/reset_password.js"></script>
  <?php endif; ?>
</body>
</html>
