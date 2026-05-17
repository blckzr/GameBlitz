<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

require_once __DIR__ . '/database/db.php';

$userId = (int) $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT user_id, full_name, email, role, created_at FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch user's inquiries by email
$inquiries = $pdo->prepare("
    SELECT inquiry_id, category, subject, message, status, created_at
    FROM   inquiries
    WHERE  email = ?
    ORDER  BY created_at DESC
");
$inquiries->execute([$user['email']]);
$inquiries = $inquiries->fetchAll();

// Fetch user's orders
$orders = $pdo->prepare("
    SELECT o.order_id, o.order_number, o.total_amount, o.status, o.created_at,
           COUNT(oi.order_item_id) AS item_count
    FROM   orders o
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    WHERE  o.user_id = ?
    GROUP  BY o.order_id
    ORDER  BY o.created_at DESC
");
$orders->execute([$userId]);
$orders = $orders->fetchAll();

$sessionUser = [
    'name'     => $_SESSION['name'],
    'is_admin' => ($_SESSION['role'] ?? '') === 'admin',
];

$statusColors = [
    'open'        => '#dc2626',
    'in_progress' => '#d97706',
    'resolved'    => '#16a34a',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile | GameBlitz</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .profile-layout {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1.5rem 4rem;
    }
    .profile-card {
      background: var(--bg-elevated, #1e2235);
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
    .profile-avatar {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: var(--accent, #bb86fc);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: 700;
      color: #1e1e2e;
      flex-shrink: 0;
    }
    .profile-info h2 { margin: 0 0 4px; font-size: 1.4rem; }
    .profile-info p  { margin: 0 0 2px; color: var(--text-muted, #888); font-size: 0.9rem; }
    .profile-role {
      display: inline-block;
      margin-top: 6px;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      background: var(--accent, #bb86fc);
      color: #1e1e2e;
    }
    .profile-actions {
      margin-left: auto;
      display: flex;
      flex-direction: column;
      gap: 8px;
      align-items: flex-end;
    }
    .section-title {
      font-size: 1.1rem;
      font-weight: 700;
      margin: 0 0 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .profile-section {
      background: var(--bg-elevated, #1e2235);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .inquiry-item {
      padding: 1rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .inquiry-item:last-child { border-bottom: none; }
    .inquiry-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 4px;
    }
    .inquiry-subject { font-weight: 600; font-size: 0.95rem; }
    .inquiry-meta    { font-size: 0.8rem; color: var(--text-muted, #888); margin-top: 2px; }
    .inquiry-status {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 10px;
      white-space: nowrap;
    }
    .order-item {
      padding: 1rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .order-item:last-child { border-bottom: none; }
    .order-number { font-weight: 700; font-family: monospace; letter-spacing: 0.05em; }
    .order-meta   { font-size: 0.82rem; color: var(--text-muted, #888); margin-top: 2px; }
    .order-status-paid      { color: #4ade80; font-weight: 600; font-size: 0.85rem; }
    .order-status-pending   { color: #fbbf24; font-weight: 600; font-size: 0.85rem; }
    .order-status-cancelled { color: #f87171; font-weight: 600; font-size: 0.85rem; }
    .empty-placeholder {
      text-align: center;
      padding: 2rem 0;
      color: var(--text-muted, #888);
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<script>window._gbUser = <?= json_encode($sessionUser) ?>;</script>

  <header class="main-header">
    <div class="nav-container">
      <a href="index.php" class="logo">Game<span>Blitz</span></a>
      <button id="mobileMenuToggle" class="mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <nav id="primaryNav">
        <ul>
          <li><a href="index.php" class="nav-btn">Home</a></li>
          <li><a href="products.php" class="nav-btn">Products</a></li>
          <li><a href="contact.html" class="nav-btn">Contact</a></li>
        </ul>
      </nav>
      <div class="nav-actions">
        <div class="nav-user-menu">
          <a href="profile.php" class="nav-action active" aria-label="My Profile">
            <span class="nav-action-icon">&#128100;</span>
            <span class="nav-action-label"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> &#9660;</span>
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
        <a href="cart.html" class="nav-action cart-link" aria-label="View shopping cart">
          <span class="nav-action-icon">&#128722;</span>
          <span class="nav-action-label">Cart</span>
          <span class="cart-badge" id="cartCount">0</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <div class="profile-layout">

      <!-- Profile card -->
      <div class="profile-card">
        <div class="profile-avatar" id="profileAvatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="profile-info">
          <h2 id="profileName"><?= htmlspecialchars($user['full_name']) ?></h2>
          <p id="profileEmail"><?= htmlspecialchars($user['email']) ?></p>
          <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
          <span class="profile-role"><?= ucfirst($user['role']) ?></span>
        </div>
        <div class="profile-actions">
          <button class="btn-secondary" id="editProfileBtn" style="font-size:0.85rem;padding:8px 16px">&#9998; Edit Profile</button>
          <?php if ($sessionUser['is_admin']): ?>
          <a href="admin/index.php" class="btn-secondary" style="font-size:0.85rem;padding:8px 16px">Admin Panel</a>
          <?php endif; ?>
          <a href="api/logout.php" class="btn-secondary" style="font-size:0.85rem;padding:8px 16px">Sign Out</a>
        </div>
      </div>

      <!-- Edit Profile form (hidden until button clicked) -->
      <div class="profile-section" id="editProfileSection" style="display:none">
        <h3 class="section-title">&#9998; Edit Profile</h3>
        <div id="editProfileAlert" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:0.875rem"></div>
        <form id="editProfileForm" style="display:flex;flex-direction:column;gap:14px">
          <div style="display:flex;gap:16px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
              <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:5px;color:var(--text-secondary,#c8cfe0)">Full Name</label>
              <input type="text" id="editFullName" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"
                style="width:100%;padding:9px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.06);color:inherit;font-size:0.9rem;box-sizing:border-box" />
              <span id="errFullName" style="font-size:0.78rem;color:#ff5555;display:none"></span>
            </div>
            <div style="flex:1;min-width:200px">
              <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:5px;color:var(--text-secondary,#c8cfe0)">Email Address</label>
              <input type="email" id="editEmail" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                style="width:100%;padding:9px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.06);color:inherit;font-size:0.9rem;box-sizing:border-box" />
              <span id="errEmail" style="font-size:0.78rem;color:#ff5555;display:none"></span>
            </div>
          </div>
          <div style="display:flex;gap:16px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
              <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:5px;color:var(--text-secondary,#c8cfe0)">New Password <em style="font-weight:400;opacity:0.6">(leave blank to keep)</em></label>
              <input type="password" id="editPassword" name="password"
                style="width:100%;padding:9px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.06);color:inherit;font-size:0.9rem;box-sizing:border-box" />
              <span id="errPassword" style="font-size:0.78rem;color:#ff5555;display:none"></span>
            </div>
            <div style="flex:1;min-width:200px">
              <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:5px;color:var(--text-secondary,#c8cfe0)">Confirm New Password</label>
              <input type="password" id="editPasswordConfirm" name="password_confirm"
                style="width:100%;padding:9px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.06);color:inherit;font-size:0.9rem;box-sizing:border-box" />
              <span id="errPasswordConfirm" style="font-size:0.78rem;color:#ff5555;display:none"></span>
            </div>
          </div>
          <div style="display:flex;gap:10px;margin-top:4px">
            <button type="submit" class="btn-primary" id="editProfileSubmit" style="padding:10px 24px">Save Changes</button>
            <button type="button" id="cancelEditBtn" class="btn-secondary" style="padding:10px 20px">Cancel</button>
          </div>
        </form>
      </div>

      <!-- Orders -->
      <div class="profile-section">
        <h3 class="section-title">&#128230; My Orders</h3>
        <?php if ($orders): ?>
          <?php foreach ($orders as $o): ?>
          <div class="order-item">
            <div>
              <div class="order-number"><?= htmlspecialchars($o['order_number']) ?></div>
              <div class="order-meta">
                <?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?> &bull;
                <?= date('M j, Y', strtotime($o['created_at'])) ?>
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:700">&#8369;<?= number_format((float)$o['total_amount']) ?></div>
              <div class="order-status-<?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-placeholder">
          <p>&#128230; You haven&rsquo;t placed any orders yet.</p>
          <a href="products.php" class="btn-primary" style="display:inline-block;margin-top:12px">Browse Games</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- Inquiries -->
      <div class="profile-section" id="inquiries">
        <h3 class="section-title">&#128140; My Inquiries</h3>
        <?php if ($inquiries): ?>
          <?php foreach ($inquiries as $inq):
            $statusColor = $statusColors[$inq['status']] ?? '#888';
          ?>
          <div class="inquiry-item">
            <div class="inquiry-header">
              <div>
                <div class="inquiry-subject"><?= htmlspecialchars($inq['subject']) ?></div>
                <div class="inquiry-meta">
                  <?= ucfirst(str_replace('_', ' ', $inq['category'])) ?> &bull;
                  <?= date('M j, Y', strtotime($inq['created_at'])) ?>
                </div>
              </div>
              <span class="inquiry-status" style="background:<?= $statusColor ?>22;color:<?= $statusColor ?>">
                <?= ucfirst(str_replace('_', ' ', $inq['status'])) ?>
              </span>
            </div>
            <p style="margin:4px 0 0;font-size:0.85rem;color:var(--text-muted,#888)">
              <?= htmlspecialchars(mb_strimwidth($inq['message'], 0, 120, '…')) ?>
            </p>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-placeholder">
          <p>&#128140; You haven&rsquo;t submitted any inquiries yet.</p>
          <a href="contact.html" class="btn-secondary" style="display:inline-block;margin-top:12px">Contact Support</a>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <footer class="main-footer">
    <div class="footer-grid">
      <div>
        <p class="footer-brand">Game<span>Blitz</span></p>
        <p>Your nationwide destination for games and gear.</p>
      </div>
      <div>
        <h5>Shop</h5>
        <ul>
          <li><a href="products.php">All Games</a></li>
        </ul>
      </div>
      <div>
        <h5>Support</h5>
        <ul>
          <li><a href="contact.html">Contact Us</a></li>
        </ul>
      </div>
    </div>
    <p class="footer-bottom">&copy; <?= date('Y') ?> GameBlitz | All Rights Reserved</p>
  </footer>

  <script src="js/cart.js"></script>
  <script>
  (function () {
    var editBtn    = document.getElementById('editProfileBtn');
    var cancelBtn  = document.getElementById('cancelEditBtn');
    var section    = document.getElementById('editProfileSection');
    var form       = document.getElementById('editProfileForm');
    var alert      = document.getElementById('editProfileAlert');
    var submitBtn  = document.getElementById('editProfileSubmit');

    var errFields = {
      full_name:        document.getElementById('errFullName'),
      email:            document.getElementById('errEmail'),
      password:         document.getElementById('errPassword'),
      password_confirm: document.getElementById('errPasswordConfirm'),
    };

    function clearErrors() {
      Object.values(errFields).forEach(function (el) { if (el) { el.textContent = ''; el.style.display = 'none'; } });
      alert.style.display = 'none';
    }

    editBtn.addEventListener('click', function () {
      section.style.display = section.style.display === 'none' ? 'block' : 'none';
      if (section.style.display === 'block') section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    cancelBtn.addEventListener('click', function () {
      section.style.display = 'none';
      clearErrors();
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearErrors();
      submitBtn.disabled    = true;
      submitBtn.textContent = 'Saving…';

      var data = new FormData(form);

      fetch('api/profile_update.php', { method: 'POST', body: data })
        .then(function (res) { return res.json(); })
        .then(function (json) {
          submitBtn.disabled    = false;
          submitBtn.textContent = 'Save Changes';

          if (!json.ok) {
            if (json.errors) {
              Object.keys(json.errors).forEach(function (key) {
                var el = errFields[key];
                if (el) { el.textContent = json.errors[key]; el.style.display = 'block'; }
              });
            } else {
              alert.textContent    = json.error || 'Failed to save. Please try again.';
              alert.style.background = 'rgba(255,85,85,0.12)';
              alert.style.color      = '#ff5555';
              alert.style.display    = 'block';
            }
            return;
          }

          // Update the displayed name/email
          document.getElementById('profileName').textContent  = json.name;
          document.getElementById('profileEmail').textContent = json.email;
          document.getElementById('profileAvatar').textContent = json.name.charAt(0).toUpperCase();

          // Clear password fields
          document.getElementById('editPassword').value        = '';
          document.getElementById('editPasswordConfirm').value = '';

          alert.textContent    = '✓ Profile updated successfully.';
          alert.style.background = 'rgba(74,222,128,0.12)';
          alert.style.color      = '#4ade80';
          alert.style.display    = 'block';

          setTimeout(function () { section.style.display = 'none'; clearErrors(); }, 1800);
        })
        .catch(function () {
          submitBtn.disabled    = false;
          submitBtn.textContent = 'Save Changes';
          alert.textContent    = 'Network error. Please try again.';
          alert.style.background = 'rgba(255,85,85,0.12)';
          alert.style.color      = '#ff5555';
          alert.style.display    = 'block';
        });
    });
  })();
  </script>
</body>
</html>
