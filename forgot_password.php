<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password | GameBlitz</title>
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
        <h1>Forgot your password?</h1>
        <p class="auth-subtitle">Enter your account email and we&rsquo;ll generate a reset link for you.</p>
      </div>

      <form id="forgotForm" class="auth-form" novalidate>
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" autocomplete="email" placeholder="example@email.com" />
          <span id="emailError" class="error-msg" aria-live="polite"></span>
        </div>

        <button type="submit" class="submit-btn" id="forgotBtn">Send Reset Link</button>
      </form>

      <div id="resetResult" style="display:none;margin-top:1rem;padding:14px;background:rgba(74,222,128,.1);border:1px solid #4ade80;border-radius:8px;color:#c8cfe0;font-size:0.9rem">
        <p style="margin:0 0 8px;font-weight:600;color:#4ade80">&#10003; Reset link generated</p>
        <p style="margin:0 0 8px">For demo purposes (no email server on localhost), click the link below to reset your password:</p>
        <a id="resetLink" href="#" style="display:block;padding:10px;background:rgba(0,0,0,0.2);border-radius:6px;word-break:break-all;font-family:monospace;font-size:0.82rem;color:var(--accent);text-decoration:none">Loading&hellip;</a>
        <p style="margin:8px 0 0;font-size:0.78rem;opacity:0.7">This link expires in 1 hour.</p>
      </div>

      <p class="auth-switch">
        Remembered your password? <a href="signin.php">Back to sign in &rsaquo;</a>
      </p>
    </div>
  </main>

  <script src="js/cart.js"></script>
  <script src="js/forgot_password.js"></script>
</body>
</html>
