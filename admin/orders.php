<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId   = (int) $_POST['order_id'];
    $newStatus = $_POST['status'] ?? '';
    $valid     = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];
    if ($orderId && in_array($newStatus, $valid)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")
            ->execute([$newStatus, $orderId]);
    }
    header('Location: orders.php?flash=saved');
    exit;
}

$flash        = $_GET['flash'] ?? '';
$validStatuses = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];
$filterStatus  = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : '';

$params = [];
$where  = '';
if ($filterStatus) {
    $where    = 'WHERE o.status = ?';
    $params[] = $filterStatus;
}

$stmt = $pdo->prepare("
    SELECT  o.order_id, o.order_number, o.status, o.total_amount, o.created_at,
            u.full_name, u.email,
            COUNT(oi.order_item_id) AS item_count
    FROM    orders o
    LEFT JOIN users       u  ON u.user_id   = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    $where
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$counts = $pdo->query("
    SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$statusColors = [
    'pending'   => ['bg' => 'rgba(251,191,36,.15)',  'color' => '#b45309'],
    'confirmed' => ['bg' => 'rgba(74,222,128,.15)',  'color' => '#166534'],
    'shipped'   => ['bg' => 'rgba(96,165,250,.15)',  'color' => '#1e40af'],
    'completed' => ['bg' => 'rgba(167,139,250,.15)', 'color' => '#5b21b6'],
    'cancelled' => ['bg' => 'rgba(252,165,165,.15)', 'color' => '#991b1b'],
];

$adminName = $_SESSION['name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Orders — Admin | GameBlitz</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />
  <style>
    .order-detail-row { display: none; background: #f8f9fc; }
    .order-detail-row.open { display: table-row; }
    .order-detail-cell {
      padding: 16px 16px 16px 52px !important;
      border-bottom: 1px solid #e0e6f0;
      font-size: 0.875rem;
      color: #374151;
    }
    .order-items-list { margin: 0; padding: 0; list-style: none; }
    .order-items-list li { padding: 4px 0; border-bottom: 1px solid #e8ecf2; }
    .order-items-list li:last-child { border-bottom: none; }
    .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .filter-chip {
      padding: 5px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
      text-decoration: none; background: #f0f2f5; color: #6b7a99; border: 1px solid #e0e6f0;
    }
    .filter-chip.active, .filter-chip:hover { background: #1e1e2e; color: #fff; border-color: #1e1e2e; }
    .order-number-cell { font-family: monospace; font-weight: 700; letter-spacing: 0.04em; font-size: 0.85rem; }
  </style>
</head>
<body class="admin-body">
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <a href="../index.php" class="logo">Game<span>Blitz</span></a>
      <span class="admin-badge">Admin</span>
    </div>
    <nav class="admin-nav">
      <ul>
        <li><a href="index.php"     class="admin-nav-link">&#128202; Dashboard</a></li>
        <li><a href="products.php"  class="admin-nav-link">&#127918; Products</a></li>
        <li><a href="users.php"     class="admin-nav-link">&#128101; Users</a></li>
        <li><a href="orders.php"    class="admin-nav-link active">&#128230; Orders</a></li>
        <li><a href="inquiries.php" class="admin-nav-link">&#128140; Inquiries</a></li>
        <li class="admin-nav-divider"></li>
        <li><a href="../index.php"       class="admin-nav-link">&#127968; View Store</a></li>
        <li><a href="../api/logout.php"  class="admin-nav-link admin-nav-logout">&#128682; Sign Out</a></li>
      </ul>
    </nav>
    <div class="admin-sidebar-user">Signed in as<br><strong><?= htmlspecialchars($adminName) ?></strong></div>
  </aside>

  <!-- Main content -->
  <main class="admin-content">
    <div class="admin-page-header">
      <div>
        <h1>&#128230; Orders</h1>
        <p class="admin-page-subtitle">View and update customer order statuses.</p>
      </div>
    </div>

    <?php if ($flash === 'saved'): ?>
    <div class="admin-flash admin-flash-success">&#10003; Order status updated.</div>
    <?php endif; ?>

    <!-- Filter chips -->
    <div class="filter-chips">
      <a href="orders.php" class="filter-chip <?= !$filterStatus ? 'active' : '' ?>">
        All (<?= array_sum($counts) ?>)
      </a>
      <?php foreach (['pending', 'confirmed', 'shipped', 'completed', 'cancelled'] as $s): ?>
      <a href="orders.php?status=<?= $s ?>" class="filter-chip <?= $filterStatus === $s ? 'active' : '' ?>">
        <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
      </a>
      <?php endforeach; ?>
    </div>

    <div class="admin-card">
      <table class="admin-table" id="ordersTable">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Date</th>
            <th>Status</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$orders): ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af">No orders found.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o):
          $sc = $statusColors[$o['status']] ?? ['bg' => '#f0f2f5', 'color' => '#374151'];
        ?>
          <tr style="cursor:pointer" onclick="toggleDetail(<?= $o['order_id'] ?>)">
            <td class="order-number-cell"><?= htmlspecialchars($o['order_number']) ?></td>
            <td>
              <div style="font-weight:500"><?= htmlspecialchars($o['full_name'] ?? 'Guest') ?></div>
              <div style="font-size:0.78rem;color:#6b7a99"><?= htmlspecialchars($o['email'] ?? '') ?></div>
            </td>
            <td><?= (int)$o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
            <td style="font-weight:600">&#8369;<?= number_format((float)$o['total_amount']) ?></td>
            <td style="font-size:0.82rem;color:#6b7a99"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
            <td>
              <span class="badge-chip" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td onclick="event.stopPropagation()">
              <form method="POST" style="display:flex;gap:6px;align-items:center">
                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                <select name="status" style="padding:4px 8px;border-radius:5px;border:1px solid #d0d7e3;font-size:0.8rem">
                  <?php foreach (['pending', 'confirmed', 'shipped', 'completed', 'cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Save</button>
              </form>
            </td>
          </tr>
          <tr class="order-detail-row" id="detail-<?= $o['order_id'] ?>">
            <td class="order-detail-cell" colspan="7">
              <?php
                $items = $pdo->prepare("
                    SELECT product_name, quantity, unit_price, subtotal
                    FROM   order_items
                    WHERE  order_id = ?
                    ORDER  BY order_item_id
                ");
                $items->execute([$o['order_id']]);
                $lineItems = $items->fetchAll();
              ?>
              <strong>Order Items:</strong>
              <ul class="order-items-list" style="margin-top:8px">
                <?php foreach ($lineItems as $li): ?>
                <li>
                  <?= htmlspecialchars($li['product_name']) ?>
                  &times; <?= (int)$li['quantity'] ?>
                  &mdash; <strong>&#8369;<?= number_format((float)$li['subtotal']) ?></strong>
                  <span style="color:#9ca3af;font-size:0.78rem">(&#8369;<?= number_format((float)$li['unit_price']) ?> each)</span>
                </li>
                <?php endforeach; ?>
              </ul>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<script>
function toggleDetail(id) {
  var row = document.getElementById('detail-' + id);
  if (row) row.classList.toggle('open');
}
</script>
</body>
</html>
