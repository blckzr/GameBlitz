<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

// Stat queries
$totalProducts   = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$outOfStock      = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0 AND is_active = 1")->fetchColumn();
$registeredUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalOrders     = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders   = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$openInquiries   = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'open'")->fetchColumn();

// Recent products (last 5 by creation)
$recentProducts = $pdo->query("
    SELECT product_id, name, price, badge
    FROM   products
    ORDER  BY product_id DESC
    LIMIT  5
")->fetchAll();

// Recent open inquiries (last 5)
$recentInquiries = $pdo->query("
    SELECT name, category, subject, created_at
    FROM   inquiries
    WHERE  status = 'open'
    ORDER  BY created_at DESC
    LIMIT  5
")->fetchAll();

// Low stock (active products with stock <= 5)
$lowStock = $pdo->query("
    SELECT product_id, name, stock
    FROM   products
    WHERE  stock <= 5 AND is_active = 1
    ORDER  BY stock ASC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Admin | GameBlitz</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />
  <style>
    .dash-stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.75rem;
    }
    .dash-card {
      background: #fff;
      border-radius: 10px;
      padding: 1.25rem 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
      border: 1px solid #e0e6f0;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .dash-card-value {
      font-size: 2rem;
      font-weight: 700;
      line-height: 1;
      color: #1a1a2e;
    }
    .dash-card-label {
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #6b7a99;
    }
    .dash-card-sub {
      font-size: 0.72rem;
      color: #9ca3af;
    }
    .dash-card.alert-red   .dash-card-value { color: #dc2626; }
    .dash-card.alert-amber .dash-card-value { color: #d97706; }
    .dash-section-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #374151;
      margin: 1.5rem 0 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .dash-quick-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-bottom: 1.75rem;
    }
    .low-stock-badge {
      display: inline-block;
      padding: 0.2rem 0.6rem;
      border-radius: 4px;
      font-size: 0.78rem;
      font-weight: 600;
    }
    .stock-zero { background: rgba(220,38,38,.1);  color: #dc2626; }
    .stock-low  { background: rgba(217,119,6,.1);  color: #d97706; }
  </style>
</head>
<body class="admin-body">
  <div class="admin-layout">

    <aside class="admin-sidebar">
      <div class="admin-brand">
        <a href="../index.php" class="logo">Game<span>Blitz</span></a>
        <span class="admin-badge">Admin</span>
      </div>
      <nav class="admin-nav">
        <ul>
          <li><a href="index.php" class="admin-nav-link active">&#128202; Dashboard</a></li>
          <li><a href="products.php" class="admin-nav-link">&#127918; Products</a></li>
          <li><a href="users.php" class="admin-nav-link">&#128101; Users</a></li>
          <li><a href="orders.php" class="admin-nav-link">&#128230; Orders</a></li>
          <li><a href="inquiries.php" class="admin-nav-link">&#128140; Inquiries</a></li>
          <li class="admin-nav-divider"></li>
          <li><a href="../index.php" class="admin-nav-link">&#127968; View Store</a></li>
          <li><a href="../api/logout.php" class="admin-nav-link admin-nav-logout">&#128682; Sign Out</a></li>
        </ul>
      </nav>
      <div class="admin-sidebar-user">
        Signed in as<br /><strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
      </div>
    </aside>

    <main class="admin-content">
      <div class="admin-page-header">
        <div>
          <h1>Dashboard</h1>
          <p class="admin-page-subtitle">Store overview at a glance</p>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="dash-stats">
        <div class="dash-card">
          <span class="dash-card-value"><?= $totalProducts ?></span>
          <span class="dash-card-label">Active Products</span>
        </div>
        <div class="dash-card <?= $outOfStock > 0 ? 'alert-red' : '' ?>">
          <span class="dash-card-value"><?= $outOfStock ?></span>
          <span class="dash-card-label">Out of Stock</span>
          <span class="dash-card-sub">active listings</span>
        </div>
        <div class="dash-card">
          <span class="dash-card-value"><?= $registeredUsers ?></span>
          <span class="dash-card-label">Registered Users</span>
        </div>
        <div class="dash-card">
          <span class="dash-card-value"><?= $totalOrders ?></span>
          <span class="dash-card-label">Total Orders</span>
        </div>
        <div class="dash-card <?= $pendingOrders > 0 ? 'alert-amber' : '' ?>">
          <span class="dash-card-value"><?= $pendingOrders ?></span>
          <span class="dash-card-label">Pending Orders</span>
        </div>
        <div class="dash-card <?= $openInquiries > 0 ? 'alert-red' : '' ?>">
          <span class="dash-card-value"><?= $openInquiries ?></span>
          <span class="dash-card-label">Open Inquiries</span>
        </div>
      </div>

      <!-- Quick Actions -->
      <p class="dash-section-title">Quick Actions</p>
      <div class="dash-quick-actions">
        <a href="product_add.php" class="admin-btn admin-btn-primary">+ Add Product</a>
        <a href="users.php" class="admin-btn">&#128101; View All Users</a>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

        <!-- Recent Products -->
        <div>
          <p class="dash-section-title">Recent Products</p>
          <div class="admin-card">
            <?php if ($recentProducts): ?>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Price</th>
                  <th>Badge</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentProducts as $p): ?>
                <tr>
                  <td><?= $p['product_id'] ?></td>
                  <td class="td-name">
                    <a href="product_edit.php?id=<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']) ?></a>
                  </td>
                  <td>&#8369;<?= number_format((float)$p['price']) ?></td>
                  <td><?= $p['badge'] ? '<span class="badge-chip badge-' . htmlspecialchars($p['badge']) . '">' . htmlspecialchars($p['badge']) . '</span>' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
            <p style="padding:1rem;opacity:0.5">No products yet.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Low Stock Alerts -->
        <div>
          <p class="dash-section-title">Low Stock Alerts</p>
          <div class="admin-card">
            <?php if ($lowStock): ?>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Stock</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lowStock as $ls): ?>
                <tr>
                  <td class="td-name"><?= htmlspecialchars($ls['name']) ?></td>
                  <td>
                    <span class="low-stock-badge <?= $ls['stock'] == 0 ? 'stock-zero' : 'stock-low' ?>">
                      <?= $ls['stock'] == 0 ? 'Out of stock' : $ls['stock'] . ' left' ?>
                    </span>
                  </td>
                  <td><a href="product_edit.php?id=<?= $ls['product_id'] ?>" class="admin-btn admin-btn-sm">Edit</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
            <p style="padding:1rem;opacity:0.5">All products are well-stocked.</p>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Recent Open Inquiries -->
      <?php if ($recentInquiries): ?>
      <p class="dash-section-title">Recent Open Inquiries</p>
      <div class="admin-card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Subject</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentInquiries as $inq): ?>
            <tr>
              <td><?= htmlspecialchars($inq['name']) ?></td>
              <td><?= htmlspecialchars(ucfirst($inq['category'])) ?></td>
              <td><?= htmlspecialchars($inq['subject']) ?></td>
              <td><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </main>

  </div>
</body>
</html>
