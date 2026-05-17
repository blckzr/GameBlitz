<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

$id = (int) ($_GET['id'] ?? $_POST['user_id'] ?? 0);
if (!$id) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare("SELECT user_id, full_name, email, role, is_active FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: users.php?flash=error');
    exit;
}

$isSelf = ((int) $id === (int) $_SESSION['user_id']);

$errors = [];
$values = [
    'full_name' => $user['full_name'],
    'email'     => $user['email'],
    'role'      => $user['role'],
    'is_active' => $user['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['full_name'] = trim($_POST['full_name'] ?? '');
    $values['email']     = trim($_POST['email'] ?? '');
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    // Admins cannot change their own role
    if (!$isSelf) {
        $values['role'] = in_array($_POST['role'] ?? '', ['customer', 'admin']) ? $_POST['role'] : $user['role'];
    } else {
        $values['role'] = $user['role'];
    }

    $newPassword     = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (strlen($values['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // Email uniqueness (exclude current user)
    if (!isset($errors['email'])) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$values['email'], $id]);
        if ((int) $check->fetchColumn() > 0) {
            $errors['email'] = 'This email is already in use by another account.';
        }
    }

    // Password change is optional — only validate if filled
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($newPassword !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
    }

    if (!$errors) {
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?, password = ?, role = ?, is_active = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$values['full_name'], $values['email'], $hash, $values['role'], $values['is_active'], $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?, role = ?, is_active = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$values['full_name'], $values['email'], $values['role'], $values['is_active'], $id]);
        }
        header('Location: users.php?flash=saved');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit User — Admin | GameBlitz</title>
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
          <h1>Edit User #<?= $id ?></h1>
          <p class="admin-page-subtitle"><a href="users.php">&larr; Back to Users</a></p>
        </div>
      </div>

      <div class="admin-card admin-form-card">
        <form method="POST" class="admin-form" autocomplete="off">
          <input type="hidden" name="user_id" value="<?= $id ?>" />

          <div class="form-row">
            <div class="form-group form-group-grow">
              <label for="full_name">Full Name <span class="req">*</span></label>
              <input type="text" id="full_name" name="full_name"
                value="<?= htmlspecialchars($values['full_name']) ?>" required />
              <?php if (!empty($errors['full_name'])): ?>
              <span class="form-error"><?= htmlspecialchars($errors['full_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="role">Role</label>
              <?php if ($isSelf): ?>
              <input type="text" value="<?= ucfirst($values['role']) ?>" disabled />
              <span style="font-size:0.75rem;opacity:0.5">Cannot change your own role.</span>
              <?php else: ?>
              <select id="role" name="role">
                <option value="customer" <?= $values['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                <option value="admin"    <?= $values['role'] === 'admin'    ? 'selected' : '' ?>>Admin</option>
              </select>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email"
              value="<?= htmlspecialchars($values['email']) ?>" required />
            <?php if (!empty($errors['email'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['email']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-row">
            <div class="form-group form-group-grow">
              <label for="password">New Password <em>(leave blank to keep current)</em></label>
              <input type="password" id="password" name="password" minlength="8" />
              <?php if (!empty($errors['password'])): ?>
              <span class="form-error"><?= htmlspecialchars($errors['password']) ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group form-group-grow">
              <label for="password_confirm">Confirm New Password</label>
              <input type="password" id="password_confirm" name="password_confirm" minlength="8" />
              <?php if (!empty($errors['password_confirm'])): ?>
              <span class="form-error"><?= htmlspecialchars($errors['password_confirm']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!$isSelf): ?>
          <div class="form-group">
            <label class="checkbox-item">
              <input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> />
              Account active (user can sign in)
            </label>
          </div>
          <?php endif; ?>

          <div class="form-actions">
            <a href="users.php" class="admin-btn">Cancel</a>
            <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
          </div>

        </form>
      </div>
    </main>

  </div>
</body>
</html>
