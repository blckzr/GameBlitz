<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

// Flash message from redirect
$flash = $_GET['flash'] ?? '';

// Fetch all products (active + inactive) with category name
$products = $pdo->query("
    SELECT  p.product_id, p.name, p.price, p.sale_price, p.stock,
            p.badge, p.is_featured, p.is_active, p.image_url,
            c.name AS category_name
    FROM    products p
    JOIN    categories c ON c.category_id = p.category_id
    ORDER   BY p.is_active DESC, p.product_id DESC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Products — Admin | GameBlitz</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body class="admin-body">
  <div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-brand">
        <a href="../index.html" class="logo">Game<span>Blitz</span></a>
        <span class="admin-badge">Admin</span>
      </div>
      <nav class="admin-nav">
        <ul>
          <li><a href="products.php" class="admin-nav-link active">&#127918; Products</a></li>
          <li class="admin-nav-divider"></li>
          <li><a href="../index.html" class="admin-nav-link">&#127968; View Store</a></li>
          <li><a href="../api/logout.php" class="admin-nav-link admin-nav-logout">&#128682; Sign Out</a></li>
        </ul>
      </nav>
      <div class="admin-sidebar-user">
        Signed in as<br /><strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
      </div>
    </aside>

    <!-- Main content -->
    <main class="admin-content">
      <div class="admin-page-header">
        <div>
          <h1>Products</h1>
          <p class="admin-page-subtitle"><?= count($products) ?> total &bull; <?= count(array_filter($products, fn($p) => $p['is_active'])) ?> active</p>
        </div>
        <a href="product_add.php" class="admin-btn admin-btn-primary">+ Add Product</a>
      </div>

      <?php if ($flash === 'saved'): ?>
      <div class="admin-flash admin-flash-success">&#10003; Product saved successfully.</div>
      <?php elseif ($flash === 'deleted'): ?>
      <div class="admin-flash admin-flash-success">&#10003; Product deleted.</div>
      <?php elseif ($flash === 'error'): ?>
      <div class="admin-flash admin-flash-error">&#9888; Something went wrong. Please try again.</div>
      <?php endif; ?>

      <div class="admin-card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Image</th>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Badge</th>
              <th>Featured</th>
              <th>Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
            <tr class="<?= $p['is_active'] ? '' : 'row-inactive' ?>">
              <td><?= $p['product_id'] ?></td>
              <td>
                <img
                  src="../<?= htmlspecialchars($p['image_url']) ?>"
                  alt="<?= htmlspecialchars($p['name']) ?>"
                  class="admin-thumb"
                />
              </td>
              <td class="td-name"><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['category_name']) ?></td>
              <td>
                <?php if ($p['sale_price']): ?>
                  <span class="price-sale">&#8369;<?= number_format((float)$p['sale_price']) ?></span><br>
                  <span class="price-strike">&#8369;<?= number_format((float)$p['price']) ?></span>
                <?php else: ?>
                  &#8369;<?= number_format((float)$p['price']) ?>
                <?php endif; ?>
              </td>
              <td class="<?= $p['stock'] == 0 ? 'td-zero' : '' ?>"><?= $p['stock'] ?></td>
              <td><?= $p['badge'] ? '<span class="badge-chip badge-' . htmlspecialchars($p['badge']) . '">' . htmlspecialchars($p['badge']) . '</span>' : '—' ?></td>
              <td><?= $p['is_featured'] ? '&#11088;' : '—' ?></td>
              <td><?= $p['is_active'] ? '<span class="status-active">Yes</span>' : '<span class="status-inactive">No</span>' ?></td>
              <td class="td-actions">
                <a href="product_edit.php?id=<?= $p['product_id'] ?>" class="admin-btn admin-btn-sm">Edit</a>
                <form method="POST" action="product_delete.php" onsubmit="return confirm('Delete &quot;<?= htmlspecialchars(addslashes($p['name'])) ?>&quot;? This cannot be undone.');" style="display:inline">
                  <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>" />
                  <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>

  </div>
</body>
</html>
