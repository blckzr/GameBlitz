<?php
session_start();
require_once __DIR__ . '/database/db.php';

$sessionUser = !empty($_SESSION['user_id']) ? [
    'name'     => $_SESSION['name'],
    'email'    => $_SESSION['email'],
    'is_admin' => ($_SESSION['role'] ?? '') === 'admin',
] : null;

// Today's Deals — up to 4 sale products, highest discount first
$deals = $pdo->query("
    SELECT p.product_id, p.name, p.price, p.sale_price, p.badge, p.image_url
    FROM   products p
    WHERE  p.is_active = 1
      AND  (p.badge = 'sale' OR p.sale_price IS NOT NULL)
    ORDER  BY (p.price - IFNULL(p.sale_price, 0)) DESC
    LIMIT  4
")->fetchAll();

// Latest Releases carousel — up to 8 featured/new products
$carousel = $pdo->query("
    SELECT p.product_id, p.name, p.price, p.sale_price, p.badge, p.image_url
    FROM   products p
    WHERE  p.is_active = 1
      AND  (p.badge = 'new' OR p.is_featured = 1)
    ORDER  BY p.created_at DESC
    LIMIT  8
")->fetchAll();

// Platform counts for category tiles
$pcRaw = $pdo->query("
    SELECT pl.slug, COUNT(DISTINCT pp.product_id) AS cnt
    FROM   platforms pl
    LEFT JOIN product_platforms pp ON pp.platform_id = pl.platform_id
    LEFT JOIN products p ON p.product_id = pp.product_id AND p.is_active = 1
    GROUP  BY pl.platform_id, pl.slug
")->fetchAll();
$platCount = [];
foreach ($pcRaw as $row) {
    $platCount[$row['slug']] = (int) $row['cnt'];
}

function dealBadge(string $badge, float $price, ?float $sale): array {
    if ($badge === 'sale' && $sale && $price) {
        return ['cls' => 'badge-sale', 'lbl' => '-' . round((1 - $sale / $price) * 100) . '%'];
    }
    return match ($badge) {
        'new'      => ['cls' => 'badge-new',      'lbl' => 'New'],
        'hot'      => ['cls' => 'badge-hot',      'lbl' => 'Hot'],
        'preorder' => ['cls' => 'badge-preorder', 'lbl' => 'Pre-Order'],
        default    => ['cls' => 'badge-sale',     'lbl' => 'Sale'],
    };
}

function carouselSuffix(string $badge): string {
    return match ($badge) {
        'sale'     => 'On sale',
        'new'      => 'New',
        'preorder' => 'Pre-order',
        'hot'      => 'Hot',
        default    => 'Shop now',
    };
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GameBlitz | Premium Gaming Store</title>
    <link rel="stylesheet" href="css/style.css" />
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
            <li><a href="index.php" class="nav-btn active">Home</a></li>
            <li><a href="products.php" class="nav-btn">Products</a></li>
            <li><a href="contact.php" class="nav-btn">Contact</a></li>
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
      <section class="hero">
        <div class="hero-content">
          <p class="hero-eyebrow">New &amp; Trending</p>
          <h1 class="hero-title">
            Level Up Your <span>Game Library</span>
          </h1>
          <p class="hero-subtitle">
            The latest releases, exclusive pre-orders, and the best deals on
            consoles and gear &mdash; shipped nationwide.
          </p>
          <div class="hero-cta">
            <a href="products.php" class="btn-primary">Shop New Releases</a>
            <a href="#deals" class="btn-secondary">View Today's Deals</a>
          </div>
        </div>
      </section>

      <section id="deals" class="deals-strip" aria-label="Today's deals">
        <div class="section-heading">
          <h2>Today&rsquo;s Deals</h2>
          <a href="products.php" class="section-link">See all &rsaquo;</a>
        </div>
        <div class="deals-grid">
          <?php if ($deals): ?>
            <?php foreach ($deals as $d):
              $price = (float) $d['price'];
              $sale  = $d['sale_price'] !== null ? (float) $d['sale_price'] : null;
              $badge = dealBadge($d['badge'] ?? '', $price, $sale);
            ?>
            <a href="products.php" class="deal-card">
              <span class="card-badge <?= $badge['cls'] ?>"><?= $badge['lbl'] ?></span>
              <img src="<?= htmlspecialchars($d['image_url']) ?>" alt="<?= htmlspecialchars($d['name']) ?>" loading="lazy" />
              <div class="deal-body">
                <h4><?= htmlspecialchars($d['name']) ?></h4>
                <p class="card-price">
                  <?php if ($sale): ?>
                  <span class="price-current">&#8369;<?= number_format($sale) ?></span>
                  <span class="price-original">&#8369;<?= number_format($price) ?></span>
                  <?php else: ?>
                  <span class="price-current">&#8369;<?= number_format($price) ?></span>
                  <?php endif; ?>
                </p>
              </div>
            </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="padding:1rem;color:var(--text-muted,#aaa)">No deals available right now. Check back soon!</p>
          <?php endif; ?>
        </div>
      </section>

      <section id="new-releases" class="releases-section">
        <div class="section-heading">
          <h2>Latest Releases</h2>
          <a href="products.php" class="section-link">Browse all games &rsaquo;</a>
        </div>
        <p class="section-sub">
          The most anticipated titles available this week &mdash; tap to shop.
        </p>

        <?php if ($carousel): ?>
        <div class="carousel-container">
          <button id="prevBtn" class="carousel-control" aria-label="Previous slide">&#10094;</button>

          <div class="carousel-viewport">
            <div id="carouselTrack" class="carousel-track">
              <?php foreach ($carousel as $c):
                $suffix = carouselSuffix($c['badge'] ?? '');
              ?>
              <a href="products.php" class="carousel-item">
                <img src="<?= htmlspecialchars($c['image_url']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" loading="lazy" />
                <span class="carousel-caption"><?= htmlspecialchars($c['name']) ?> &mdash; <?= $suffix ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>

          <button id="nextBtn" class="carousel-control" aria-label="Next slide">&#10095;</button>
        </div>
        <?php else: ?>
        <p style="text-align:center;padding:2rem;color:var(--text-muted,#aaa)">New releases coming soon!</p>
        <?php endif; ?>
      </section>

      <section id="categories" class="categories-section">
        <div class="section-heading">
          <h2>Shop by Category</h2>
        </div>
        <p class="section-sub">
          Browse our collection of hardware and software.
        </p>

        <?php
        // Query categories with product counts
        $genreCategories = $pdo->query("
            SELECT c.slug, c.name, COUNT(DISTINCT p.product_id) AS cnt
            FROM   categories c
            LEFT JOIN products p ON p.category_id = c.category_id AND p.is_active = 1
            GROUP  BY c.category_id
            ORDER  BY cnt DESC
        ")->fetchAll();

        $categoryIcons = [
            'rpg'        => '&#9876;',
            'action'     => '&#128165;',
            'horror'     => '&#128123;',
            'indie'      => '&#127381;',
            'sports'     => '&#9917;',
            'racing'     => '&#127954;',
            'strategy'   => '&#9822;',
            'simulation' => '&#127758;',
        ];
        $categorySubs = [
            'rpg'        => 'Epic adventures and character-driven stories',
            'action'     => 'Fast-paced combat and adrenaline-fueled gameplay',
            'horror'     => 'Survival horror and psychological thrillers',
            'indie'      => 'Unique experiences from independent studios',
            'sports'     => 'Football, basketball, and more',
            'racing'     => 'Speed, tracks, and high-octane competition',
            'strategy'   => 'Tactical thinking and resource management',
            'simulation' => 'Life, city, and world simulation games',
        ];
        ?>
        <div class="category-grid">
          <?php foreach ($genreCategories as $cat):
            $icon = $categoryIcons[$cat['slug']] ?? '&#127918;';
            $sub  = $categorySubs[$cat['slug']]  ?? 'Explore the collection';
          ?>
          <a href="products.php" class="category-tile" data-category="<?= htmlspecialchars($cat['slug']) ?>">
            <span class="category-icon" aria-hidden="true"><?= $icon ?></span>
            <h3><?= htmlspecialchars($cat['name']) ?></h3>
            <p><?= $sub ?></p>
            <?php if ((int)$cat['cnt'] > 0): ?>
            <span class="category-count"><?= $cat['cnt'] ?> title<?= $cat['cnt'] != 1 ? 's' : '' ?></span>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
    </main>

    <footer class="main-footer">
      <div class="footer-grid">
        <div>
          <p class="footer-brand">Game<span>Blitz</span></p>
          <p>Your nationwide destination for games, consoles, and gear.</p>
          <p class="footer-contact">
            support@gameblitz.com<br />
            Branches nationwide
          </p>
        </div>
        <div>
          <h5>Shop</h5>
          <ul>
            <li><a href="products.php">All Games</a></li>
            <li><a href="products.php">Consoles</a></li>
            <li><a href="products.php">Accessories</a></li>
            <li><a href="products.php">Today's Deals</a></li>
          </ul>
        </div>
        <div>
          <h5>Support</h5>
          <ul>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="#">Order Tracking</a></li>
            <li><a href="#">Warranty</a></li>
            <li><a href="#">Store Locator</a></li>
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
    <script src="js/carousel.js"></script>
  </body>
</html>
