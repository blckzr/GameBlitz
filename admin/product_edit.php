<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../database/db.php';

$id = (int) ($_GET['id'] ?? $_POST['product_id'] ?? 0);
if (!$id) {
    header('Location: products.php');
    exit;
}

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();
$platforms  = $pdo->query("SELECT platform_id, slug, name FROM platforms ORDER BY name")->fetchAll();

// Fetch existing product
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: products.php?flash=error');
    exit;
}

// Fetch current platform IDs for this product
$ppStmt = $pdo->prepare("SELECT platform_id FROM product_platforms WHERE product_id = ?");
$ppStmt->execute([$id]);
$currentPlatforms = $ppStmt->fetchAll(PDO::FETCH_COLUMN);

$errors = [];
$values = [
    'name'        => $product['name'],
    'category_id' => $product['category_id'],
    'description' => $product['description'] ?? '',
    'price'       => (int) $product['price'],
    'sale_price'  => $product['sale_price'] !== null ? (int) $product['sale_price'] : '',
    'image_url'   => $product['image_url'],
    'stock'       => $product['stock'],
    'badge'       => $product['badge'] ?? '',
    'is_featured' => $product['is_featured'],
    'is_active'   => $product['is_active'],
    'platforms'   => array_map('intval', $currentPlatforms),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name']        = trim($_POST['name'] ?? '');
    $values['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $values['description'] = trim($_POST['description'] ?? '');
    $values['price']       = trim($_POST['price'] ?? '');
    $values['sale_price']  = trim($_POST['sale_price'] ?? '');
    $values['stock']       = (int) ($_POST['stock'] ?? 0);
    $values['badge']       = $_POST['badge'] ?? '';
    $values['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $values['is_active']   = isset($_POST['is_active']) ? 1 : 0;
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

    // Handle file upload
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($_FILES['image_file']['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            $errors['image_url'] = 'Only JPEG and PNG images are allowed.';
        } else {
            $ext     = $allowed[$mime];
            $base    = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $values['name'] ?: 'product')));
            $base    = trim($base, '-') ?: 'product';
            $dest    = __DIR__ . '/../assets/img/' . $base . '.' . $ext;
            $counter = 1;
            while (file_exists($dest)) {
                $dest = __DIR__ . '/../assets/img/' . $base . '-' . $counter++ . '.' . $ext;
            }
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
                $values['image_url'] = 'assets/img/' . basename($dest);
            } else {
                $errors['image_url'] = 'Upload failed. Check folder permissions on assets/img/.';
            }
        }
    }

    if (!$errors) {
        $salePrice = $values['sale_price'] !== '' ? (float) $values['sale_price'] : null;
        $badge     = $values['badge'] ?: null;

        $stmt = $pdo->prepare("
            UPDATE products
            SET    category_id = ?, name = ?, description = ?, price = ?,
                   sale_price = ?, image_url = ?, stock = ?, badge = ?,
                   is_featured = ?, is_active = ?
            WHERE  product_id = ?
        ");
        $stmt->execute([
            $values['category_id'],
            $values['name'],
            $values['description'] ?: null,
            (float) $values['price'],
            $salePrice,
            $values['image_url'],
            $values['stock'],
            $badge,
            $values['is_featured'],
            $values['is_active'],
            $id,
        ]);

        // Update platform mappings: delete old, insert new
        $pdo->prepare("DELETE FROM product_platforms WHERE product_id = ?")->execute([$id]);
        if ($values['platforms']) {
            $ins = $pdo->prepare("INSERT INTO product_platforms (product_id, platform_id) VALUES (?, ?)");
            foreach ($values['platforms'] as $pid) {
                $ins->execute([$id, $pid]);
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
  <title>Edit Product — Admin | GameBlitz</title>
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
          <li><a href="products.php" class="admin-nav-link active">&#127918; Products</a></li>
          <li><a href="users.php" class="admin-nav-link">&#128101; Users</a></li>
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
          <h1>Edit Product #<?= $id ?></h1>
          <p class="admin-page-subtitle"><a href="products.php">&larr; Back to Products</a></p>
        </div>
      </div>

      <div class="admin-card admin-form-card">
        <form method="POST" class="admin-form" enctype="multipart/form-data">
          <input type="hidden" name="product_id" value="<?= $id ?>" />

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
            <label>Product Image</label>
            <?php if ($values['image_url']): ?>
            <div class="upload-preview" id="uploadPreview" style="display:flex;margin-bottom:10px">
              <img src="../<?= htmlspecialchars($values['image_url']) ?>" alt="Current image" />
              <div><strong>Current image</strong><br><span><?= htmlspecialchars($values['image_url']) ?></span></div>
            </div>
            <?php else: ?>
            <div class="upload-preview" id="uploadPreview" style="display:none"></div>
            <?php endif; ?>
            <div class="upload-zone" id="uploadZone">
              <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png" />
              <div class="upload-zone-body" id="uploadZoneBody">
                <span class="upload-zone-icon">&#128444;</span>
                <p class="upload-zone-text">Click or drag &amp; drop to replace image</p>
                <p class="upload-zone-hint">JPEG or PNG &bull; max 5 MB &bull; leave empty to keep current</p>
              </div>
            </div>
            <?php if (!empty($errors['image_url'])): ?><span class="form-error"><?= htmlspecialchars($errors['image_url']) ?></span><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Platforms</label>
            <div class="checkbox-group">
              <?php foreach ($platforms as $pl): ?>
              <label class="checkbox-item">
                <input type="checkbox" name="platforms[]" value="<?= $pl['platform_id'] ?>"
                  <?= in_array((int)$pl['platform_id'], $values['platforms']) ? 'checked' : '' ?> />
                <?= htmlspecialchars($pl['name']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="checkbox-item">
                <input type="checkbox" name="is_featured" value="1" <?= $values['is_featured'] ? 'checked' : '' ?> />
                Featured product
              </label>
            </div>
            <div class="form-group">
              <label class="checkbox-item">
                <input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> />
                Active (visible in store)
              </label>
            </div>
          </div>

          <div class="form-actions">
            <a href="products.php" class="admin-btn">Cancel</a>
            <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
          </div>

        </form>
      </div>
    </main>

  </div>
<script>
(function () {
  var zone    = document.getElementById('uploadZone');
  var input   = document.getElementById('image_file');
  var body    = document.getElementById('uploadZoneBody');
  var preview = document.getElementById('uploadPreview');
  if (!zone || !input) return;

  zone.addEventListener('dragover',  function (e) { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', function ()  { zone.classList.remove('drag-over'); });
  zone.addEventListener('drop', function (e) {
    e.preventDefault(); zone.classList.remove('drag-over');
    if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; showPreview(e.dataTransfer.files[0]); }
  });
  input.addEventListener('change', function () {
    if (input.files.length) showPreview(input.files[0]);
  });

  function showPreview(file) {
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" /><div><strong>' + file.name + '</strong><br><span>' + (file.size / 1024).toFixed(0) + ' KB</span></div><button type="button" class="upload-clear-btn" title="Remove">&#10005;</button>';
      preview.style.display = 'flex';
      preview.querySelector('.upload-clear-btn').addEventListener('click', function () {
        input.value = ''; preview.style.display = 'none'; preview.innerHTML = '';
      });
      body.querySelector('.upload-zone-text').textContent = 'New image selected — click to change';
    };
    reader.readAsDataURL(file);
  }
})();
</script>
</body>
</html>
