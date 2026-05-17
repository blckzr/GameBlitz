<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();
$platforms  = $pdo->query("SELECT platform_id, slug, name FROM platforms ORDER BY name")->fetchAll();

$errors = [];
$values = [
    'name' => '', 'category_id' => '', 'description' => '',
    'price' => '', 'sale_price' => '', 'image_url' => '',
    'stock' => 0, 'badge' => '', 'is_featured' => 0,
    'platforms' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name']        = trim($_POST['name'] ?? '');
    $values['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $values['description'] = trim($_POST['description'] ?? '');
    $values['price']       = trim($_POST['price'] ?? '');
    $values['sale_price']  = trim($_POST['sale_price'] ?? '');
    $values['image_url']   = trim($_POST['image_url'] ?? '');
    $values['stock']       = (int) ($_POST['stock'] ?? 0);
    $values['badge']       = $_POST['badge'] ?? '';
    $values['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $values['platforms']   = array_map('intval', $_POST['platforms'] ?? []);

    // Validation
    if (strlen($values['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    }
    if (!$values['category_id']) {
        $errors['category_id'] = 'Please select a category.';
    }
    if (!is_numeric($values['price']) || (float) $values['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number.';
    }
    if ($values['sale_price'] !== '' && (!is_numeric($values['sale_price']) || (float) $values['sale_price'] >= (float) $values['price'])) {
        $errors['sale_price'] = 'Sale price must be less than the base price.';
    }
    if (!$values['image_url']) {
        $errors['image_url'] = 'Image URL/path is required.';
    }
    if ($values['stock'] < 0) {
        $errors['stock'] = 'Stock cannot be negative.';
    }

    if (!$errors) {
        // Auto-generate slug from name
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($values['name']));
        $slug = trim($slug, '-');

        // Ensure slug is unique
        $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug LIKE ?");
        $check->execute([$slug . '%']);
        $count = (int) $check->fetchColumn();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $salePrice = $values['sale_price'] !== '' ? (float) $values['sale_price'] : null;
        $badge     = $values['badge'] ?: null;

        $stmt = $pdo->prepare("
            INSERT INTO products
                (category_id, name, slug, description, price, sale_price, image_url, stock, badge, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $values['category_id'],
            $values['name'],
            $slug,
            $values['description'] ?: null,
            (float) $values['price'],
            $salePrice,
            $values['image_url'],
            $values['stock'],
            $badge,
            $values['is_featured'],
        ]);
        $productId = (int) $pdo->lastInsertId();

        // Insert platform mappings
        if ($values['platforms']) {
            $ins = $pdo->prepare("INSERT INTO product_platforms (product_id, platform_id) VALUES (?, ?)");
            foreach ($values['platforms'] as $pid) {
                $ins->execute([$productId, $pid]);
            }
        }

        header('Location: products.php?flash=saved');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Product — Admin | GameBlitz</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body class="admin-body">
  <div class="admin-layout">

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

    <main class="admin-content">
      <div class="admin-page-header">
        <div>
          <h1>Add Product</h1>
          <p class="admin-page-subtitle"><a href="products.php">&larr; Back to Products</a></p>
        </div>
      </div>

      <div class="admin-card admin-form-card">
        <form method="POST" class="admin-form">

          <div class="form-row">
            <div class="form-group form-group-grow">
              <label for="name">Product Name <span class="req">*</span></label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($values['name']) ?>" required />
              <?php if (!empty($errors['name'])): ?><span class="form-error"><?= htmlspecialchars($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
              <label for="category_id">Category <span class="req">*</span></label>
              <select id="category_id" name="category_id" required>
                <option value="">— Select —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>" <?= $values['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['category_id'])): ?><span class="form-error"><?= htmlspecialchars($errors['category_id']) ?></span><?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($values['description']) ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="price">Base Price (&#8369;) <span class="req">*</span></label>
              <input type="number" id="price" name="price" min="1" step="1" value="<?= htmlspecialchars($values['price']) ?>" required />
              <?php if (!empty($errors['price'])): ?><span class="form-error"><?= htmlspecialchars($errors['price']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
              <label for="sale_price">Sale Price (&#8369;) <em>optional</em></label>
              <input type="number" id="sale_price" name="sale_price" min="0" step="1" value="<?= htmlspecialchars($values['sale_price']) ?>" />
              <?php if (!empty($errors['sale_price'])): ?><span class="form-error"><?= htmlspecialchars($errors['sale_price']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
              <label for="stock">Stock Qty</label>
              <input type="number" id="stock" name="stock" min="0" step="1" value="<?= $values['stock'] ?>" />
              <?php if (!empty($errors['stock'])): ?><span class="form-error"><?= htmlspecialchars($errors['stock']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
              <label for="badge">Badge</label>
              <select id="badge" name="badge">
                <option value="">None</option>
                <?php foreach (['hot','new','sale','preorder'] as $b): ?>
                <option value="<?= $b ?>" <?= $values['badge'] === $b ? 'selected' : '' ?>><?= ucfirst($b) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="image_url">Image Path <span class="req">*</span></label>
            <input type="text" id="image_url" name="image_url" placeholder="assets/img/game.jpg" value="<?= htmlspecialchars($values['image_url']) ?>" />
            <?php if (!empty($errors['image_url'])): ?><span class="form-error"><?= htmlspecialchars($errors['image_url']) ?></span><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Platforms</label>
            <div class="checkbox-group">
              <?php foreach ($platforms as $pl): ?>
              <label class="checkbox-item">
                <input type="checkbox" name="platforms[]" value="<?= $pl['platform_id'] ?>"
                  <?= in_array($pl['platform_id'], $values['platforms']) ? 'checked' : '' ?> />
                <?= htmlspecialchars($pl['name']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-item">
              <input type="checkbox" name="is_featured" value="1" <?= $values['is_featured'] ? 'checked' : '' ?> />
              Featured product (shown first in store)
            </label>
          </div>

          <div class="form-actions">
            <a href="products.php" class="admin-btn">Cancel</a>
            <button type="submit" class="admin-btn admin-btn-primary">Save Product</button>
          </div>

        </form>
      </div>
    </main>

  </div>
</body>
</html>
