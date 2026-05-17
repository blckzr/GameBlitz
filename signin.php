<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    $dest = ($_SESSION['role'] === 'admin') ? 'admin/products.php' : 'index.html';
    header('Location: ' . $dest);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In | GameBlitz</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/auth.css" />
</head>
<body>
  <header class="main-header">
    <div class="nav-container">
      <a href="index.html" class="logo">Game<span>Blitz</span></a>
      <button id="mobileMenuToggle" class="mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <nav id="primaryNav">
        <ul>
          <li><a href="index.html" class="nav-btn">Home</a></li>
          <li><a href="products.php" class="nav-btn">Products</a></li>
          <li><a href="contact.php" class="nav-btn">Contact</a></li>
        </ul>
      </nav>
      <div class="nav-actions">
        <a href="signin.php" class="nav-action active" id="navSignIn" aria-label="Sign in">
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
        <h1>Welcome back</h1>
        <p class="auth-subtitle">Sign in to your account to continue</p>
      </div>

      <?php if (!empty($_GET['registered'])): ?>
      <div class="demo-notice" style="background:rgba(74,222,128,.12);border-color:#4ade80;color:#4ade80;" role="status">
        &#10003; Account created! Please sign in to continue.
      </div>
      <?php endif; ?>

      <form id="signinForm" class="auth-form" novalidate>
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" autocomplete="email" placeholder="example@email.com" />
          <span id="emailError" class="error-msg" aria-live="polite"></span>
        </div>

        <div class="input-group">
          <div class="label-row">
            <label for="password">Password</label>
            <a href="#" class="label-link" id="forgotPasswordLink">Forgot password?</a>
          </div>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Enter your password" />
            <button type="button" class="toggle-pw" id="togglePassword" aria-label="Show password">&#128065;</button>
          </div>
          <span id="passwordError" class="error-msg" aria-live="polite"></span>
        </div>

        <label class="checkbox-label">
          <input type="checkbox" id="rememberMe" name="remember" />
          <span>Keep me signed in</span>
        </label>

        <button type="submit" class="submit-btn" id="signinBtn">Sign In</button>
      </form>

      <p class="auth-switch">
        Don&rsquo;t have an account? <a href="register.php">Create one &rsaquo;</a>
      </p>
    </div>
  </main>

  <script src="js/cart.js"></script>
  <script src="js/signin.js"></script>
</body>
</html>
