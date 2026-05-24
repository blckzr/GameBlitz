<?php
session_start();
$sessionUser = !empty($_SESSION['user_id']) ? [
    'name'     => $_SESSION['name']  ?? '',
    'is_admin' => ($_SESSION['role'] ?? '') === 'admin',
] : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Terms of Service | GameBlitz</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .legal-layout { max-width: 820px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
    .legal-layout h1 { font-size: 1.8rem; margin: 0 0 8px; }
    .legal-meta { color: var(--text-muted, #888); font-size: 0.85rem; margin-bottom: 2rem; }
    .legal-section { background: var(--bg-elevated, #1e2235); border-radius: 12px; padding: 1.5rem 2rem; margin-bottom: 1.5rem; }
    .legal-section h2 { font-size: 1.15rem; margin: 0 0 12px; color: var(--accent, #bb86fc); }
    .legal-section p, .legal-section li { font-size: 0.95rem; line-height: 1.65; color: var(--text-secondary, #c8cfe0); }
    .legal-section ul { padding-left: 1.25rem; margin: 8px 0; }
  </style>
</head>
<body>
<?php if ($sessionUser): ?>
<script>window._gbUser = <?= json_encode($sessionUser) ?>;</script>
<?php endif; ?>

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
        <?php if ($sessionUser): ?>
        <div class="nav-user-menu">
          <a href="profile.php" class="nav-action" id="navSignIn">
            <span class="nav-action-icon">&#128100;</span>
            <span class="nav-action-label"><?= htmlspecialchars(explode(' ', $sessionUser['name'])[0]) ?> &#9660;</span>
          </a>
          <div class="nav-user-dropdown">
            <a href="profile.php">&#128100; My Profile</a>
            <a href="profile.php#inquiries">&#128140; My Inquiries</a>
            <?php if ($sessionUser['is_admin']): ?>
            <a href="admin/index.php">&#9881; Admin Panel</a>
            <?php endif; ?>
            <div class="nav-user-dropdown-divider"></div>
            <a href="api/logout.php" class="nav-user-dropdown-signout">&#128682; Sign Out</a>
          </div>
        </div>
        <?php else: ?>
        <a href="signin.php" class="nav-action" id="navSignIn">
          <span class="nav-action-icon">&#128100;</span>
          <span class="nav-action-label">Sign In</span>
        </a>
        <?php endif; ?>
        <a href="cart.html" class="nav-action cart-link">
          <span class="nav-action-icon">&#128722;</span>
          <span class="nav-action-label">Cart</span>
          <span class="cart-badge" id="cartCount">0</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span aria-hidden="true">/</span>
      <span aria-current="page">Terms of Service</span>
    </nav>

    <div class="legal-layout">
      <h1>Terms of Service</h1>
      <p class="legal-meta">Last updated: <?= date('F j, Y') ?></p>

      <div class="legal-section">
        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account or using GameBlitz, you agree to be bound by these Terms of Service. If you do not agree, please do not use the site.</p>
      </div>

      <div class="legal-section">
        <h2>2. Account Eligibility</h2>
        <p>You must be at least 13 years old to create an account. You are responsible for keeping your password secure and for all activity that happens under your account.</p>
      </div>

      <div class="legal-section">
        <h2>3. Use of the Service</h2>
        <p>GameBlitz is a platform for browsing and purchasing video games. You agree to use the service only for lawful purposes. You may not:</p>
        <ul>
          <li>Attempt to gain unauthorized access to other accounts or to admin tools</li>
          <li>Upload malicious code or attempt to disrupt the site</li>
          <li>Resell, scrape, or republish content without permission</li>
          <li>Submit fraudulent inquiries or orders</li>
        </ul>
      </div>

      <div class="legal-section">
        <h2>4. Orders and Payment</h2>
        <p>All prices are shown in Philippine Peso (&#8369;). Orders placed through GameBlitz are marked as confirmed immediately for demonstration purposes; no real payment processor is integrated. By placing an order you confirm the cart contents are correct at the time of checkout.</p>
      </div>

      <div class="legal-section">
        <h2>5. Product Availability</h2>
        <p>We try to keep stock counts accurate, but availability may change without notice. If an item becomes unavailable after you order, we will cancel that line and notify you via the inquiries system.</p>
      </div>

      <div class="legal-section">
        <h2>6. Account Termination</h2>
        <p>GameBlitz administrators may deactivate any account that violates these Terms, abuses the contact system, or attempts to compromise site security. You may delete your account at any time by contacting support.</p>
      </div>

      <div class="legal-section">
        <h2>7. Limitation of Liability</h2>
        <p>GameBlitz is provided "as is" without warranty of any kind. We are not liable for any indirect damages arising from your use of the service.</p>
      </div>

      <div class="legal-section">
        <h2>8. Changes to These Terms</h2>
        <p>We may update these Terms from time to time. Continued use of the site after changes are posted constitutes acceptance of the new Terms.</p>
      </div>

      <div class="legal-section">
        <h2>9. Contact</h2>
        <p>Questions about these Terms? Reach us through the <a href="contact.php" style="color:var(--accent)">contact page</a> or at <strong>support@gameblitz.com</strong>.</p>
      </div>
    </div>
  </main>

  <footer class="main-footer">
    <div class="footer-grid">
      <div>
        <p class="footer-brand">Game<span>Blitz</span></p>
        <p>Your nationwide destination for games, consoles, and gear.</p>
      </div>
      <div>
        <h5>Shop</h5>
        <ul>
          <li><a href="products.php">All Games</a></li>
        </ul>
      </div>
      <div>
        <h5>Legal</h5>
        <ul>
          <li><a href="terms.php">Terms of Service</a></li>
          <li><a href="privacy.php">Privacy Policy</a></li>
        </ul>
      </div>
      <div>
        <h5>Support</h5>
        <ul>
          <li><a href="contact.php">Contact Us</a></li>
        </ul>
      </div>
    </div>
    <p class="footer-bottom">&copy; <?= date('Y') ?> GameBlitz | All Rights Reserved</p>
  </footer>

  <script src="js/cart.js"></script>
</body>
</html>
