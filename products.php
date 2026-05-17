<?php
session_start();
require_once __DIR__ . '/database/db.php';

$sessionUser = !empty($_SESSION['user_id']) ? [
    'name'     => $_SESSION['name'],
    'email'    => $_SESSION['email'],
    'is_admin' => ($_SESSION['role'] ?? '') === 'admin',
] : null;

// Filter options from DB
$categories = $pdo->query("SELECT slug, name FROM categories ORDER BY name")->fetchAll();
$platforms  = $pdo->query("SELECT slug, name FROM platforms ORDER BY name")->fetchAll();

// Products with platform slugs for data-* attributes
$products = $pdo->query("
    SELECT  p.*,
            c.slug  AS category_slug,
            GROUP_CONCAT(pl.slug ORDER BY pl.slug SEPARATOR ',') AS platforms
    FROM    products p
    JOIN    categories c ON c.category_id = p.category_id
    LEFT JOIN product_platforms pp ON pp.product_id = p.product_id
    LEFT JOIN platforms pl         ON pl.platform_id = pp.platform_id
    WHERE   p.is_active = 1
    GROUP   BY p.product_id
    ORDER   BY p.is_featured DESC, p.created_at DESC
")->fetchAll();

function platformLabel(string $slug): string {
    $map = ['ps5' => 'PS5', 'xbox' => 'Xbox', 'switch' => 'Switch', 'pc' => 'PC'];
    return $map[$slug] ?? strtoupper($slug);
}

function badgeLabel(string $badge, int $price, ?int $sale): string {
    if ($badge === 'sale' && $sale && $price) {
        return '-' . round((1 - $sale / $price) * 100) . '%';
    }
    return ['hot' => 'Hot', 'new' => 'New', 'preorder' => 'Pre-Order'][$badge] ?? '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Products | GameBlitz</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/products.css" />
</head>
<body>
<?php if ($sessionUser): ?>
<script>window._gbUser = <?= json_encode($sessionUser) ?>;</script>
<?php endif; ?>

  <header class="main-header">
    <div class="nav-container">
      <a href="index.html" class="logo">Game<span>Blitz</span></a>
      <button id="mobileMenuToggle" class="mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <nav id="primaryNav">
        <ul>
          <li><a href="index.html" class="nav-btn">Home</a></li>
          <li><a href="products.php" class="nav-btn active">Products</a></li>
          <li><a href="contact.html" class="nav-btn">Contact</a></li>
        </ul>
      </nav>
      <div class="nav-actions">
        <?php if ($sessionUser): ?>
        <a href="api/logout.php" class="nav-action" id="navSignIn" aria-label="Account">
          <span class="nav-action-icon">&#128100;</span>
          <span class="nav-action-label"><?= htmlspecialchars(explode(' ', $sessionUser['name'])[0]) ?></span>
        </a>
        <?php else: ?>
        <a href="signin.php" class="nav-action" id="navSignIn" aria-label="Sign in">
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
      <a href="index.html">Home</a>
      <span aria-hidden="true">/</span>
      <span aria-current="page">Products</span>
    </nav>

    <h2>Game Collection</h2>

    <div class="filter-bar">
      <div class="search-container">
        <input type="search" id="gameSearch" placeholder="Search for a game&hellip;" aria-label="Search games" />
        <p id="searchFeedback" aria-live="polite"></p>
      </div>

      <div class="filter-controls">
        <label class="filter-field">
          <span>Platform</span>
          <select id="platformFilter">
            <option value="">All Platforms</option>
            <?php foreach ($platforms as $pl): ?>
            <option value="<?= htmlspecialchars($pl['slug']) ?>"><?= htmlspecialchars($pl['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="filter-field">
          <span>Category</span>
          <select id="categoryFilter">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="filter-field">
          <span>Sort by</span>
          <select id="sortControl">
            <option value="featured">Featured</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
            <option value="name-asc">Name: A&ndash;Z</option>
          </select>
        </label>
      </div>
    </div>

    <div id="productGrid" class="product-grid">
      <?php foreach ($products as $p):
        $price    = (int) $p['price'];
        $sale     = $p['sale_price'] !== null ? (int) $p['sale_price'] : null;
        $badge    = $p['badge'] ?? '';
        $slugs    = $p['platforms'] ?? '';
        $platList = $slugs ? explode(',', $slugs) : [];
        $btnLabel = $badge === 'preorder' ? 'Pre-Order Now' : 'Add to Cart';
      ?>
      <article
        class="product-card"
        data-product-id="<?= $p['product_id'] ?>"
        data-name="<?= htmlspecialchars($p['name']) ?>"
        data-price="<?= $price ?>"
        data-sale-price="<?= $sale ?? '' ?>"
        data-platform="<?= htmlspecialchars($slugs) ?>"
        data-category="<?= htmlspecialchars($p['category_slug']) ?>"
        data-stock="<?= (int) $p['stock'] ?>"
        data-badge="<?= htmlspecialchars($badge) ?>"
      >
        <div class="card-media">
          <?php if ($badge): $lbl = badgeLabel($badge, $price, $sale); ?>
          <?php if ($lbl): ?>
          <span class="card-badge badge-<?= htmlspecialchars($badge) ?>"><?= htmlspecialchars($lbl) ?></span>
          <?php endif; endif; ?>
          <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" />
        </div>
        <div class="card-body">
          <div class="card-platforms" aria-label="Platforms">
            <?php foreach ($platList as $slug): ?>
            <span class="platform-tag"><?= htmlspecialchars(platformLabel($slug)) ?></span>
            <?php endforeach; ?>
          </div>
          <h4 class="card-title"><?= htmlspecialchars($p['name']) ?></h4>
          <p class="card-price">
            <?php if ($sale): ?>
            <span class="price-current">&#8369;<?= number_format($sale) ?></span>
            <span class="price-original">&#8369;<?= number_format($price) ?></span>
            <?php else: ?>
            <span class="price-current">&#8369;<?= number_format($price) ?></span>
            <?php endif; ?>
          </p>
          <button class="add-to-cart" type="button"><?= htmlspecialchars($btnLabel) ?></button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <p id="emptyState" class="empty-state" hidden>
      No games match your filters. Try clearing them.
    </p>
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
          <li><a href="products.php">Consoles</a></li>
          <li><a href="products.php">Accessories</a></li>
        </ul>
      </div>
      <div>
        <h5>Support</h5>
        <ul>
          <li><a href="contact.html">Contact Us</a></li>
          <li><a href="#">Order Tracking</a></li>
          <li><a href="#">Warranty</a></li>
        </ul>
      </div>
    </div>
    <p class="footer-bottom">&copy; <?= date('Y') ?> GameBlitz | All Rights Reserved</p>
  </footer>

  <script src="js/cart.js"></script>
  <script src="js/products.js"></script>
</body>
</html>
