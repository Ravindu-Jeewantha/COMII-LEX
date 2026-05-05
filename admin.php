<?php
require_once 'config.php';

/* ─── Simple password protection ─── */
define('ADMIN_PASSWORD', 'admin123'); // ← CHANGE THIS

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $loginError = 'Incorrect password.';
        }
    }
    if (!isset($_SESSION['admin_logged_in'])) {
        showLogin($loginError ?? null);
        exit;
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

$db = getDB();
$messages = [];
$errors = [];

/* ─── Handle Actions ─── */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Add Product ──
if ($action === 'add_product') {
    $name  = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc  = trim($_POST['description'] ?? '');
    $image = '';

    if (!$name || !$brand || $price <= 0) {
        $errors[] = 'Name, brand, and a valid price are required.';
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $result = uploadImage($_FILES['image']);
            if ($result['success']) {
                $image = $result['filename'];
            } else {
                $errors[] = $result['error'];
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO products (name, brand, price, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssdss', $name, $brand, $price, $desc, $image);
            if ($stmt->execute()) {
                $messages[] = "✅ Product \"$name\" added successfully!";
            } else {
                $errors[] = 'Database error: ' . $db->error;
            }
        }
    }
}

// ── Upload Image to Existing Product ──
if ($action === 'upload_image') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId && !empty($_FILES['image']['name'])) {
        $result = uploadImage($_FILES['image']);
        if ($result['success']) {
            $stmt = $db->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmt->bind_param('si', $result['filename'], $productId);
            $stmt->execute();
            $messages[] = "✅ Image updated for product #$productId";
        } else {
            $errors[] = $result['error'];
        }
    }
}

// ── Delete Product ──
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Also remove from cart
    $db->prepare("DELETE FROM cart WHERE product_id = ?")->execute() ;
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $messages[] = "🗑️ Product #$id deleted.";
}

// ── Edit Product (inline update) ──
if ($action === 'edit_product') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc  = trim($_POST['description'] ?? '');

    if ($id && $name && $brand && $price > 0) {
        // Handle optional image re-upload
        $imageClause = '';
        $imageFile = '';
        if (!empty($_FILES['image']['name'])) {
            $result = uploadImage($_FILES['image']);
            if ($result['success']) {
                $imageFile = $result['filename'];
            } else {
                $errors[] = $result['error'];
            }
        }

        if (empty($errors)) {
            if ($imageFile) {
                $stmt = $db->prepare("UPDATE products SET name=?, brand=?, price=?, description=?, image=? WHERE id=?");
                $stmt->bind_param('ssdssi', $name, $brand, $price, $desc, $imageFile, $id);
            } else {
                $stmt = $db->prepare("UPDATE products SET name=?, brand=?, price=?, description=? WHERE id=?");
                $stmt->bind_param('ssdsi', $name, $brand, $price, $desc, $id);
            }
            $stmt->execute();
            $messages[] = "✅ Product #$id updated.";
        }
    } else {
        $errors[] = 'All fields are required.';
    }
}

/* ─── Image Upload Helper ─── */
function uploadImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, WebP, and GIF are allowed.'];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Image must be under 5MB.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $dest = __DIR__ . '/images/' . $filename;

    if (!is_dir(__DIR__ . '/images/')) {
        mkdir(__DIR__ . '/images/', 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'filename' => $filename];
    }
    return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
}

/* ─── Fetch all products ─── */
$products = $db->query("SELECT * FROM products ORDER BY id DESC");
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editProduct = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
}

/* ─── Show Login Page ─── */
function showLogin($error = null) { ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Comii Lex</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:40px;width:100%;max-width:380px;text-align:center;">
    <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,#6366f1,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px;">Comii Lex</div>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:28px;">Admin Panel</p>
    <?php if ($error): ?>
      <div style="background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);border-radius:8px;padding:10px 14px;color:#f87171;font-size:0.85rem;margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" style="display:flex;flex-direction:column;gap:12px;">
      <input type="password" name="password" placeholder="Admin password" autofocus
        style="padding:12px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:0.9rem;outline:none;">
      <button type="submit" style="padding:12px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:0.9rem;">
        Enter Admin Panel →
      </button>
    </form>
    <p style="margin-top:20px;font-size:0.78rem;color:var(--text-dim);">Default password: <code style="color:var(--accent)">admin123</code></p>
  </div>
  <script src="script.js"></script>
</body>
</html>
<?php }
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Comii Lex</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .admin-layout { display: grid; grid-template-columns: 300px 1fr; gap: 28px; padding: 28px clamp(16px,4vw,48px); max-width: 1400px; margin: 0 auto; align-items: start; }
    .admin-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 28px; position: sticky; top: 80px; }
    .admin-panel h2 { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; letter-spacing: 0.06em; text-transform: uppercase; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.88rem; outline: none; transition: border-color 0.2s; font-family: var(--font-body); }
    .form-group input:focus, .form-group textarea:focus { border-color: var(--accent); }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .upload-zone { border: 2px dashed var(--border); border-radius: 10px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; }
    .upload-zone:hover, .upload-zone.drag { border-color: var(--accent); background: rgba(99,102,241,0.05); }
    .upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .upload-zone-text { font-size: 0.85rem; color: var(--text-muted); }
    .upload-zone-icon { font-size: 2rem; margin-bottom: 8px; }
    .upload-preview { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-top: 12px; display: none; }
    .product-table { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
    .table-header { display: grid; grid-template-columns: 64px 1fr 120px 120px 180px 100px; gap: 16px; padding: 14px 20px; border-bottom: 1px solid var(--border); font-size: 0.75rem; font-weight: 600; color: var(--text-muted); letter-spacing: 0.08em; text-transform: uppercase; }
    .table-row { display: grid; grid-template-columns: 64px 1fr 120px 120px 180px 100px; gap: 16px; padding: 16px 20px; border-bottom: 1px solid var(--border); align-items: center; transition: background 0.2s; }
    .table-row:last-child { border-bottom: none; }
    .table-row:hover { background: var(--bg-card-hover); }
    .table-img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; background: var(--bg-2); flex-shrink: 0; }
    .product-name-cell { font-weight: 600; font-size: 0.88rem; color: var(--text); }
    .product-brand-cell { font-size: 0.75rem; color: var(--accent); font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; margin-top: 3px; }
    .row-actions { display: flex; gap: 8px; }
    .alert { padding: 12px 16px; border-radius: 10px; font-size: 0.88rem; margin-bottom: 16px; }
    .alert-success { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.25); color: #34d399; }
    .alert-error { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.25); color: #f87171; }
    .quick-upload { display: flex; gap: 8px; align-items: center; }
    .quick-upload input[type=file] { font-size: 0.8rem; color: var(--text-muted); background: var(--bg); border: 1px solid var(--border); border-radius: 6px; padding: 6px 10px; }
    .tab-bar { display: flex; gap: 4px; margin-bottom: 24px; }
    .tab { padding: 8px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); cursor: pointer; border: 1px solid transparent; transition: all 0.2s; background: transparent; }
    .tab.active { background: rgba(99,102,241,0.12); border-color: rgba(99,102,241,0.3); color: var(--accent); }
    @media(max-width:900px) { .admin-layout { grid-template-columns: 1fr; } .admin-panel { position: static; } .table-header, .table-row { grid-template-columns: 48px 1fr 90px 100px; } .table-header > *:nth-child(4), .table-row > *:nth-child(4), .table-header > *:nth-child(5), .table-row > *:nth-child(5) { display: none; } }
    @media(max-width:600px) { .table-header, .table-row { grid-template-columns: 48px 1fr 90px; } .table-header > *:nth-child(3), .table-row > *:nth-child(3) { display: none; } }
  </style>
</head>
<body>

<div id="toast-container" class="toast-container"></div>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">Comii Lex</a>
  <ul class="nav-links">
    <li><a href="index.php">← Store</a></li>
    <li><a href="products.php">Products</a></li>
    <li><a href="admin.php" class="active">Admin</a></li>
  </ul>
  <div class="nav-actions">
    <button class="theme-toggle" id="theme-btn" onclick="toggleTheme()">☀️</button>
    <a href="admin.php?logout=1" class="btn btn-ghost btn-sm">Logout</a>
  </div>
</nav>

<!-- Page Banner -->
<div class="page-banner">
  <h1>Admin Panel</h1>
  <p>Manage your products and images</p>
</div>

<div class="admin-layout">

  <!-- Left: Add / Edit Form -->
  <div>
    <!-- Flash messages -->
    <?php foreach ($messages as $msg): ?>
      <div class="alert alert-success"><?= e($msg) ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="admin-panel">
      <?php if ($editProduct): ?>
        <h2>✏️ Edit Product #<?= $editProduct['id'] ?></h2>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="edit_product">
          <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
      <?php else: ?>
        <h2>➕ Add New Product</h2>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_product">
      <?php endif; ?>

        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" required placeholder="e.g. MacBook Pro 16-inch M3"
            value="<?= e($editProduct['name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Brand *</label>
          <input type="text" name="brand" required placeholder="e.g. Apple"
            value="<?= e($editProduct['brand'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Price (USD) *</label>
          <input type="number" name="price" required step="0.01" min="0.01" placeholder="0.00"
            value="<?= e($editProduct['price'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4" placeholder="Product description..."><?= e($editProduct['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label><?= $editProduct ? 'Replace Image (optional)' : 'Product Image' ?></label>
          <div class="upload-zone" id="upload-zone">
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
              onchange="previewImage(this)">
            <div class="upload-zone-icon">📸</div>
            <div class="upload-zone-text">
              Click or drag & drop<br>
              <small style="color:var(--text-dim)">JPG, PNG, WebP, GIF · Max 5MB</small>
            </div>
            <?php if (!empty($editProduct['image'])): ?>
            <img src="<?= productImage($editProduct['image']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-top:12px;" alt="current">
            <small style="color:var(--text-dim);display:block;margin-top:6px;">Current image above — upload to replace</small>
            <?php endif; ?>
            <img id="preview-img" class="upload-preview" alt="preview">
          </div>
        </div>

        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-primary" style="flex:1">
            <?= $editProduct ? '💾 Save Changes' : '➕ Add Product' ?>
          </button>
          <?php if ($editProduct): ?>
          <a href="admin.php" class="btn btn-outline">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Right: Product Table -->
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <div>
        <div class="section-label">Database</div>
        <h2 style="font-family:var(--font-display);font-size:1.3rem;font-weight:800;letter-spacing:-0.02em;">
          All Products
          <span style="font-size:0.9rem;font-weight:500;color:var(--text-muted);margin-left:8px;">(<?= $products->num_rows ?>)</span>
        </h2>
      </div>
      <a href="products.php" target="_blank" class="btn btn-outline btn-sm">View Store →</a>
    </div>

    <div class="product-table">
      <div class="table-header">
        <span>Image</span>
        <span>Product</span>
        <span>Price</span>
        <span>Brand</span>
        <span>Quick Upload</span>
        <span>Actions</span>
      </div>

      <?php if ($products->num_rows === 0): ?>
        <div style="padding:48px;text-align:center;color:var(--text-muted)">
          No products yet. Add your first one →
        </div>
      <?php else:
        while ($p = $products->fetch_assoc()): ?>
        <div class="table-row">
          <!-- Image -->
          <img class="table-img"
               src="<?= productImage($p['image']) ?>"
               alt="<?= e($p['name']) ?>"
               id="thumb-<?= $p['id'] ?>">

          <!-- Name + Brand -->
          <div>
            <div class="product-name-cell"><?= e($p['name']) ?></div>
            <div class="product-brand-cell"><?= e($p['brand']) ?></div>
          </div>

          <!-- Price -->
          <div style="font-family:var(--font-display);font-weight:800;font-size:0.95rem;">
            <?= price($p['price']) ?>
          </div>

          <!-- Brand -->
          <div style="font-size:0.82rem;color:var(--text-muted)"><?= e($p['brand']) ?></div>

          <!-- Quick image upload -->
          <form method="POST" enctype="multipart/form-data" class="quick-upload"
                onsubmit="submitQuickUpload(event, <?= $p['id'] ?>)">
            <input type="hidden" name="action" value="upload_image">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="file" name="image" accept="image/*"
              style="font-size:0.75rem;color:var(--text-muted);background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:5px 8px;max-width:120px;"
              onchange="this.form.submit()"
              title="Upload image for this product">
          </form>

          <!-- Actions -->
          <div class="row-actions">
            <a href="admin.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm" title="Edit">✏️</a>
            <a href="admin.php?action=delete&id=<?= $p['id'] ?>"
               class="btn btn-danger btn-sm"
               title="Delete"
               onclick="return confirm('Delete \'<?= e(addslashes($p['name'])) ?>\'? This cannot be undone.')">🗑️</a>
          </div>
        </div>
      <?php endwhile; endif; ?>
    </div>

    <!-- Tip box -->
    <div style="margin-top:20px;padding:20px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.15);border-radius:var(--radius);font-size:0.85rem;color:var(--text-muted);line-height:1.6;">
      <strong style="color:var(--accent-2);">💡 Image Tips</strong><br>
      Use the <strong>Quick Upload</strong> column to instantly assign an image to any product — just pick a file and it uploads automatically.<br>
      For best results use <strong>square images (1:1)</strong> or <strong>4:3 ratio</strong>, min 400×400px. WebP gives the smallest file size.
    </div>
  </div>

</div>

<!-- Footer -->
<footer>
  <span class="footer-logo">Comii Lex</span>
  <span>Admin Panel · Logged in</span>
  <a href="admin.php?logout=1" style="color:var(--text-muted);font-size:0.85rem;">Logout →</a>
</footer>

<script src="script.js"></script>
<script>
function previewImage(input) {
  const preview = document.getElementById('preview-img');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Drag and drop styling
const zone = document.getElementById('upload-zone');
if (zone) {
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
  zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('drag'); });
}

function submitQuickUpload(e, productId) {
  // Allow normal form submit — page will reload with message
  // Could be AJAX but reload is simpler for admin
}

// Scroll to top of edit form if editing
<?php if ($editProduct): ?>
document.querySelector('.admin-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>

// Auto-dismiss alerts after 5s
document.querySelectorAll('.alert').forEach(a => {
  setTimeout(() => { a.style.opacity = '0'; a.style.transition = '0.4s'; setTimeout(() => a.remove(), 400); }, 5000);
});
</script>
</body>
</html>
