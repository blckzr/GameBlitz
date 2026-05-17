<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

$flash = $_GET['flash'] ?? '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inquiry_id'])) {
    $inquiryId = (int) $_POST['inquiry_id'];
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['open', 'in_progress', 'resolved'];
    if ($inquiryId && in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE inquiries SET status = ? WHERE inquiry_id = ?")
            ->execute([$newStatus, $inquiryId]);
    }
    header('Location: inquiries.php?flash=saved');
    exit;
}

$filterStatus = $_GET['status'] ?? '';
$whereClause  = $filterStatus ? "WHERE status = " . $pdo->quote($filterStatus) : '';

$inquiries = $pdo->query("
    SELECT inquiry_id, name, email, category, order_number, subject, message, status, created_at
    FROM   inquiries
    $whereClause
    ORDER  BY created_at DESC
")->fetchAll();

$counts = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM   inquiries
    GROUP  BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inquiries — Admin | GameBlitz</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />
  <style>
    .inquiry-message {
      max-width: 320px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #6b7a99;
      font-size: 0.82rem;
    }
    .inquiry-detail-row { display: none; background: #f8f9fc; }
    .inquiry-detail-row.open { display: table-row; }
    .inquiry-detail-cell {
      padding: 16px 16px 16px 52px !important;
      border-bottom: 1px solid #e0e6f0;
      font-size: 0.875rem;
      color: #374151;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .status-filter-bar {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .status-filter-btn {
      padding: 6px 14px;
      border-radius: 20px;
      border: 1px solid #d0d7e3;
      background: #fff;
      color: #374151;
      font-size: 0.82rem;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
    }
    .status-filter-btn.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
    .status-open       { background: rgba(220,38,38,.1);  color: #dc2626; }
    .status-in_progress{ background: rgba(217,119,6,.1);  color: #d97706; }
    .status-resolved   { background: rgba(22,163,74,.1);  color: #16a34a; }
    .status-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 0.75rem;
      font-weight: 600;
    }
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
          <li><a href="index.php" class="admin-nav-link">&#128202; Dashboard</a></li>
          <li><a href="products.php" class="admin-nav-link">&#127918; Products</a></li>
          <li><a href="users.php" class="admin-nav-link">&#128101; Users</a></li>
          <li><a href="inquiries.php" class="admin-nav-link active">&#128140; Inquiries</a></li>
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
          <h1>Inquiries</h1>
          <p class="admin-page-subtitle"><?= count($inquiries) ?> shown &bull; <?= $counts['open'] ?? 0 ?> open</p>
        </div>
      </div>

      <?php if ($flash === 'saved'): ?>
      <div class="admin-flash admin-flash-success">&#10003; Status updated.</div>
      <?php endif; ?>

      <!-- Status filter -->
      <div class="status-filter-bar">
        <a href="inquiries.php" class="status-filter-btn <?= !$filterStatus ? 'active' : '' ?>">All (<?= array_sum($counts) ?>)</a>
        <a href="inquiries.php?status=open"        class="status-filter-btn <?= $filterStatus === 'open' ? 'active' : '' ?>">Open (<?= $counts['open'] ?? 0 ?>)</a>
        <a href="inquiries.php?status=in_progress" class="status-filter-btn <?= $filterStatus === 'in_progress' ? 'active' : '' ?>">In Progress (<?= $counts['in_progress'] ?? 0 ?>)</a>
        <a href="inquiries.php?status=resolved"    class="status-filter-btn <?= $filterStatus === 'resolved' ? 'active' : '' ?>">Resolved (<?= $counts['resolved'] ?? 0 ?>)</a>
      </div>

      <div class="admin-card">
        <?php if (!$inquiries): ?>
        <p style="padding:2rem;text-align:center;color:#9ca3af">No inquiries found.</p>
        <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Category</th>
              <th>Subject</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inquiries as $inq): ?>
            <tr class="inquiry-row" style="cursor:pointer" onclick="toggleDetail(<?= $inq['inquiry_id'] ?>)">
              <td><?= $inq['inquiry_id'] ?></td>
              <td>
                <strong><?= htmlspecialchars($inq['name']) ?></strong><br>
                <span style="font-size:0.78rem;color:#9ca3af"><?= htmlspecialchars($inq['email']) ?></span>
              </td>
              <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($inq['category']))) ?></td>
              <td>
                <span><?= htmlspecialchars($inq['subject']) ?></span><br>
                <span class="inquiry-message"><?= htmlspecialchars($inq['message']) ?></span>
              </td>
              <td style="white-space:nowrap"><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
              <td>
                <span class="status-badge status-<?= htmlspecialchars($inq['status']) ?>">
                  <?= ucfirst(str_replace('_', ' ', $inq['status'])) ?>
                </span>
              </td>
              <td class="td-actions" onclick="event.stopPropagation()">
                <form method="POST" style="display:flex;gap:4px;align-items:center">
                  <input type="hidden" name="inquiry_id" value="<?= $inq['inquiry_id'] ?>" />
                  <select name="status" class="admin-btn admin-btn-sm" style="padding:4px 8px;cursor:pointer">
                    <option value="open"        <?= $inq['status']==='open'        ? 'selected':'' ?>>Open</option>
                    <option value="in_progress" <?= $inq['status']==='in_progress' ? 'selected':'' ?>>In Progress</option>
                    <option value="resolved"    <?= $inq['status']==='resolved'    ? 'selected':'' ?>>Resolved</option>
                  </select>
                  <button type="submit" class="admin-btn admin-btn-sm admin-btn-primary">Save</button>
                </form>
              </td>
            </tr>
            <tr id="detail-<?= $inq['inquiry_id'] ?>" class="inquiry-detail-row">
              <td colspan="7" class="inquiry-detail-cell">
                <?php if ($inq['order_number']): ?>
                <strong>Order #:</strong> <?= htmlspecialchars($inq['order_number']) ?><br><br>
                <?php endif; ?>
                <strong>Full message:</strong><br><?= htmlspecialchars($inq['message']) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
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
