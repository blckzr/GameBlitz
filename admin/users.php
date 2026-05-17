<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

$flash = $_GET['flash'] ?? '';

// Handle role toggle (promote/demote) via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_role'])) {
    $targetId  = (int) ($_POST['user_id'] ?? 0);
    $newRole   = $_POST['new_role'] ?? '';

    if ($targetId && $targetId !== (int) $_SESSION['user_id'] && in_array($newRole, ['customer', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$newRole, $targetId]);
    }
    header('Location: users.php?flash=saved');
    exit;
}

$users = $pdo->query("
    SELECT user_id, full_name, email, role, is_active, created_at
    FROM   users
    ORDER  BY role DESC, created_at DESC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users — Admin | GameBlitz</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />
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
          <li><a href="users.php" class="admin-nav-link active">&#128101; Users</a></li>
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
          <h1>Users</h1>
          <p class="admin-page-subtitle"><?= count($users) ?> total &bull; <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?> admin(s)</p>
        </div>
        <a href="user_add.php" class="admin-btn admin-btn-primary">+ Add User</a>
      </div>

      <?php if ($flash === 'saved'): ?>
      <div class="admin-flash admin-flash-success">&#10003; Changes saved.</div>
      <?php elseif ($flash === 'deleted'): ?>
      <div class="admin-flash admin-flash-success">&#10003; User deactivated.</div>
      <?php elseif ($flash === 'error'): ?>
      <div class="admin-flash admin-flash-error">&#9888; Something went wrong. Please try again.</div>
      <?php endif; ?>

      <div class="admin-card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Active</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u):
              $isSelf = ((int) $u['user_id'] === (int) $_SESSION['user_id']);
            ?>
            <tr class="<?= $u['is_active'] ? '' : 'row-inactive' ?>">
              <td><?= $u['user_id'] ?></td>
              <td class="td-name"><?= htmlspecialchars($u['full_name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <span class="badge-chip badge-<?= $u['role'] === 'admin' ? 'hot' : 'new' ?>">
                  <?= ucfirst($u['role']) ?>
                </span>
              </td>
              <td><?= $u['is_active'] ? '<span class="status-active">Yes</span>' : '<span class="status-inactive">No</span>' ?></td>
              <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td class="td-actions">
                <a href="user_edit.php?id=<?= $u['user_id'] ?>" class="admin-btn admin-btn-sm">Edit</a>

                <?php if (!$isSelf): ?>
                  <!-- Role toggle -->
                  <?php if ($u['role'] === 'customer'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="toggle_role" value="1" />
                    <input type="hidden" name="user_id"    value="<?= $u['user_id'] ?>" />
                    <input type="hidden" name="new_role"   value="admin" />
                    <button type="submit" class="admin-btn admin-btn-sm"
                      onclick="return confirm('Promote <?= htmlspecialchars(addslashes($u['full_name'])) ?> to Admin?')">
                      Promote
                    </button>
                  </form>
                  <?php else: ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="toggle_role" value="1" />
                    <input type="hidden" name="user_id"    value="<?= $u['user_id'] ?>" />
                    <input type="hidden" name="new_role"   value="customer" />
                    <button type="submit" class="admin-btn admin-btn-sm"
                      onclick="return confirm('Demote <?= htmlspecialchars(addslashes($u['full_name'])) ?> to Customer?')">
                      Demote
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Deactivate / Activate -->
                  <?php if ($u['is_active']): ?>
                  <form method="POST" action="user_delete.php" style="display:inline"
                    onsubmit="return confirm('Deactivate account for <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>" />
                    <input type="hidden" name="action"  value="deactivate" />
                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Deactivate</button>
                  </form>
                  <?php else: ?>
                  <form method="POST" action="user_delete.php" style="display:inline">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>" />
                    <input type="hidden" name="action"  value="activate" />
                    <button type="submit" class="admin-btn admin-btn-sm">Activate</button>
                  </form>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="opacity:0.4;font-size:0.8rem">(you)</span>
                <?php endif; ?>
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
