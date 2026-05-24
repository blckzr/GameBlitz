<?php
session_start();

$sessionUser = !empty($_SESSION['user_id']) ? [
    'name'     => $_SESSION['name']  ?? '',
    'email'    => $_SESSION['email'] ?? '',
    'is_admin' => ($_SESSION['role'] ?? '') === 'admin',
] : null;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us | GameBlitz</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/contact.css" />
  </head>
  <body>
<?php if ($sessionUser): ?>
<script>window._gbUser = <?= json_encode($sessionUser) ?>;</script>
<?php endif; ?>

    <header class="main-header">
      <div class="nav-container">
        <a href="index.php" class="logo">Game<span>Blitz</span></a>

        <button
          id="mobileMenuToggle"
          class="mobile-toggle"
          aria-label="Toggle navigation menu"
          aria-expanded="false"
        >
          <span></span><span></span><span></span>
        </button>

        <nav id="primaryNav">
          <ul>
            <li><a href="index.php"   class="nav-btn">Home</a></li>
            <li><a href="products.php" class="nav-btn">Products</a></li>
            <li><a href="contact.php" class="nav-btn active">Contact</a></li>
          </ul>
        </nav>

        <div class="nav-actions">
          <?php if ($sessionUser): ?>
          <div class="nav-user-menu">
            <a href="profile.php" class="nav-action" id="navSignIn" aria-label="Account">
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
          <a href="signin.php" class="nav-action" id="navSignIn" aria-label="Sign in to your account">
            <span class="nav-action-icon">&#128100;</span>
            <span class="nav-action-label">Sign In</span>
          </a>
          <?php endif; ?>
          <a href="cart.html" class="nav-action cart-link" aria-label="View shopping cart">
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
        <span aria-current="page">Contact</span>
      </nav>

      <div class="contact-layout">
        <section class="contact-intro">
          <h2>Contact Support</h2>
          <p>
            Have a question about a game, order, or warranty? Send us a message
            and we&rsquo;ll get back to you within 1&ndash;2 business days.
          </p>

          <ul class="contact-info">
            <li>
              <strong>Email</strong>
              <span>support@gameblitz.com</span>
            </li>
            <li>
              <strong>Hotline</strong>
              <span>+63 (2) 8888-0123</span>
            </li>
            <li>
              <strong>Hours</strong>
              <span>Mon&ndash;Sat, 10:00 AM &ndash; 8:00 PM</span>
            </li>
          </ul>
        </section>

        <form id="contactForm" class="contact-form" novalidate>
          <div class="input-row">
            <div class="input-group">
              <label for="name">Full Name</label>
              <input
                type="text"
                id="name"
                name="name"
                autocomplete="name"
                placeholder="Juan Dela Cruz"
                <?php if ($sessionUser): ?>
                value="<?= htmlspecialchars($sessionUser['name']) ?>"
                readonly
                <?php endif; ?>
              />
              <span id="nameError" class="error-msg" aria-live="polite"></span>
            </div>

            <div class="input-group">
              <label for="email">Email Address</label>
              <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                placeholder="example@email.com"
                <?php if ($sessionUser): ?>
                value="<?= htmlspecialchars($sessionUser['email']) ?>"
                readonly
                <?php endif; ?>
              />
              <span id="emailError" class="error-msg" aria-live="polite"></span>
            </div>
          </div>

          <?php if ($sessionUser): ?>
          <p style="font-size:0.8rem;color:var(--text-muted,#888);margin:-12px 0 12px">
            &#128100; Sending as <strong><?= htmlspecialchars($sessionUser['name']) ?></strong> &mdash;
            <a href="profile.php" style="color:var(--accent)">not you?</a>
          </p>
          <?php endif; ?>

          <div class="input-row">
            <div class="input-group">
              <label for="inquiryCategory">Inquiry Type</label>
              <select id="inquiryCategory" name="category">
                <option value="">Select a category...</option>
                <option value="order">Order Issue</option>
                <option value="product">Product Question</option>
                <option value="warranty">Warranty / Returns</option>
                <option value="preorder">Pre-Order</option>
                <option value="general">General Inquiry</option>
              </select>
              <span id="categoryError" class="error-msg" aria-live="polite"></span>
            </div>

            <div class="input-group">
              <label for="orderNumber">Order Number <span class="label-hint">(optional)</span></label>
              <input
                type="text"
                id="orderNumber"
                name="order_number"
                placeholder="e.g. GB-2026-00123"
              />
            </div>
          </div>

          <div class="input-group">
            <label for="subject">Subject</label>
            <input
              type="text"
              id="subject"
              name="subject"
              placeholder="Brief summary of your inquiry"
            />
            <span id="subjectError" class="error-msg" aria-live="polite"></span>
          </div>

          <div class="input-group">
            <label for="message">Your Message</label>
            <textarea
              id="message"
              name="message"
              rows="6"
              placeholder="Tell us more about what you need..."
            ></textarea>
            <span id="messageError" class="error-msg" aria-live="polite"></span>
          </div>

          <button type="submit" class="submit-btn">Send Message</button>
        </form>
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
          <h5>Support</h5>
          <ul>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="#">Order Tracking</a></li>
            <li><a href="#">Warranty</a></li>
          </ul>
        </div>
        <div>
          <h5>Legal</h5>
          <ul>
            <li><a href="terms.php">Terms of Service</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <p class="footer-bottom">&copy; <?= date('Y') ?> GameBlitz | All Rights Reserved</p>
    </footer>

    <script src="js/cart.js"></script>
    <script src="js/contact.js"></script>
  </body>
</html>
