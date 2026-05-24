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
  <title>Privacy Policy | GameBlitz</title>
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
      <span aria-current="page">Privacy Policy</span>
    </nav>

    <div class="legal-layout">
      <h1>Privacy Policy</h1>
      <p class="legal-meta">Last updated: <?= date('F j, Y') ?></p>

      <div class="legal-section">
        <h2>1. Information We Collect</h2>
        <p>When you use GameBlitz we collect:</p>
        <ul>
          <li><strong>Account information</strong> — your full name and email address when you register</li>
          <li><strong>Authentication data</strong> — your password, stored as a bcrypt hash (never plain text)</li>
          <li><strong>Order history</strong> — items, quantities, prices, and order timestamps</li>
          <li><strong>Inquiries</strong> — messages you submit through the contact form</li>
          <li><strong>Session data</strong> — a PHP session cookie that keeps you signed in</li>
          <li><strong>Cart contents</strong> — stored locally in your browser via <code>localStorage</code></li>
        </ul>
      </div>

      <div class="legal-section">
        <h2>2. How We Use Your Information</h2>
        <p>Your information is used to:</p>
        <ul>
          <li>Authenticate you and protect your account</li>
          <li>Process and fulfill your orders</li>
          <li>Respond to your support inquiries</li>
          <li>Display your order and inquiry history on your profile</li>
        </ul>
        <p>We do not sell, rent, or share your personal information with third parties.</p>
      </div>

      <div class="legal-section">
        <h2>3. How We Protect Your Information</h2>
        <ul>
          <li>Passwords are hashed with <strong>bcrypt</strong> (<code>password_hash</code>) — we never store or see your plain password.</li>
          <li>All database queries use <strong>PDO prepared statements</strong> to prevent SQL injection.</li>
          <li>Session IDs are regenerated on login to prevent session fixation attacks.</li>
          <li>Administrative tools are protected by a server-side role check on every request.</li>
        </ul>
      </div>

      <div class="legal-section">
        <h2>4. Cookies and Local Storage</h2>
        <p>GameBlitz uses:</p>
        <ul>
          <li>A <strong>PHP session cookie</strong> to keep you signed in across pages</li>
          <li><strong>Browser localStorage</strong> to remember your shopping cart between visits</li>
        </ul>
        <p>You can clear both at any time through your browser settings. Doing so will sign you out and empty your cart.</p>
      </div>

      <div class="legal-section">
        <h2>5. Your Rights</h2>
        <p>You can:</p>
        <ul>
          <li>View and edit your personal information through your <a href="profile.php" style="color:var(--accent)">profile page</a></li>
          <li>Change your password from your profile or through the "Forgot password" flow</li>
          <li>Request account deletion by contacting support</li>
          <li>Request a copy of your stored data by contacting support</li>
        </ul>
      </div>

      <div class="legal-section">
        <h2>6. Data Retention</h2>
        <p>Your account data is kept as long as your account is active. Order records may be retained for accounting purposes even after account deactivation. Inquiries are kept for support history.</p>
      </div>

      <div class="legal-section">
        <h2>7. Children&rsquo;s Privacy</h2>
        <p>GameBlitz is not intended for children under 13. We do not knowingly collect data from anyone under 13. If you believe a minor has registered, please contact us so we can remove the account.</p>
      </div>

      <div class="legal-section">
        <h2>8. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. The "Last updated" date at the top will reflect the most recent revision.</p>
      </div>

      <div class="legal-section">
        <h2>9. Contact</h2>
        <p>Questions about your data or this policy? Reach us through the <a href="contact.php" style="color:var(--accent)">contact page</a> or at <strong>support@gameblitz.com</strong>.</p>
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
