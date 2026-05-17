<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account | GameBlitz</title>
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
          <li><a href="contact.html" class="nav-btn">Contact</a></li>
        </ul>
      </nav>
      <div class="nav-actions">
        <a href="signin.php" class="nav-action" id="navSignIn" aria-label="Sign in">
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
        <h1>Create an account</h1>
        <p class="auth-subtitle">Join GameBlitz and start building your library</p>
      </div>

      <form id="registerForm" class="auth-form" novalidate>
        <div class="input-group">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="name" autocomplete="name" placeholder="Juan Dela Cruz" />
          <span id="nameError" class="error-msg" aria-live="polite"></span>
        </div>

        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" autocomplete="email" placeholder="example@email.com" />
          <span id="emailError" class="error-msg" aria-live="polite"></span>
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" />
            <button type="button" class="toggle-pw" id="togglePassword" aria-label="Show password">&#128065;</button>
          </div>
          <span id="passwordError" class="error-msg" aria-live="polite"></span>
          <div class="password-strength" id="passwordStrength" hidden>
            <div class="strength-bar">
              <div class="strength-fill" id="strengthFill"></div>
            </div>
            <span class="strength-label" id="strengthLabel"></span>
          </div>
        </div>

        <div class="input-group">
          <label for="confirmPassword">Confirm Password</label>
          <div class="password-wrap">
            <input type="password" id="confirmPassword" name="confirm_password" autocomplete="new-password" placeholder="Repeat your password" />
            <button type="button" class="toggle-pw" id="toggleConfirm" aria-label="Show password">&#128065;</button>
          </div>
          <span id="confirmError" class="error-msg" aria-live="polite"></span>
        </div>

        <label class="checkbox-label">
          <input type="checkbox" id="agreeTerms" name="terms" />
          <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
        </label>
        <span id="termsError" class="error-msg" aria-live="polite"></span>

        <button type="submit" class="submit-btn">Create Account</button>
      </form>

      <p class="auth-switch">
        Already have an account? <a href="signin.php">Sign in &rsaquo;</a>
      </p>
    </div>
  </main>

  <script src="js/cart.js"></script>
  <script src="js/register.js"></script>
</body>
</html>
