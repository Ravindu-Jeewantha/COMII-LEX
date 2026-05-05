<?php
require_once 'config.php';

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    header('Location: products.php');
    exit;
}

// Related products (same brand, exclude current)
$stmt2 = $db->prepare("SELECT * FROM products WHERE brand = ? AND id != ? LIMIT 4");
$stmt2->bind_param('si', $p['brand'], $id);
$stmt2->execute();
$related = $stmt2->get_result();

$cartCount = cartCount();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($p['name']) ?> — Comii Lex</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body>

<div id="loading-screen">
  <div class="loader-logo">Comii Lex</div>
  <div class="loader-bar"><div class="loader-bar-fill"></div></div>
</div>

<div id="toast-container" class="toast-container"></div>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">Comii Lex</a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="products.php" class="active">Products</a></li>
    <li><a href="cart.php">Cart</a></li>
  </ul>
  <div class="nav-actions">
    <button class="theme-toggle" id="theme-btn" onclick="toggleTheme()">☀️</button>
    <a href="cart.php" class="cart-icon">
      🛒
      <span class="cart-badge" id="cart-badge" style="<?= $cartCount === 0 ? 'display:none' : '' ?>"><?= $cartCount ?></span>
    </a>
    <div class="hamburger" id="hamburger" onclick="toggleMobileMenu()">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<div class="mobile-nav" id="mobile-nav">
  <a href="index.php">Home</a>
  <a href="products.php" class="active">Products</a>
  <a href="cart.php">Cart</a>
</div>

<!-- Product Detail -->
<div class="product-detail">
  <!-- Image -->
  <div class="product-detail-img-wrap">
    <img src="<?= productImage($p['image']) ?>" alt="<?= e($p['name']) ?>">
  </div>

  <!-- Info -->
  <div class="product-detail-info">
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span>/</span>
      <a href="products.php">Products</a>
      <span>/</span>
      <a href="products.php?brand=<?= urlencode($p['brand']) ?>"><?= e($p['brand']) ?></a>
      <span>/</span>
      <span style="color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;"><?= e($p['name']) ?></span>
    </div>

    <div class="detail-brand"><?= e($p['brand']) ?></div>
    <h1 class="detail-name"><?= e($p['name']) ?></h1>
    <div class="detail-price"><?= price($p['price']) ?></div>

    <p class="detail-desc"><?= nl2br(e($p['description'])) ?></p>

    <!-- Quantity + Add -->
    <div class="detail-actions">
      <div class="qty-control">
        <button class="qty-btn" onclick="changeDetailQty(-1)">−</button>
        <span class="qty-val" id="detail-qty">1</span>
        <button class="qty-btn" onclick="changeDetailQty(1)">+</button>
      </div>
      <button class="btn btn-primary" onclick="addDetailToCart(<?= $p['id'] ?>, '<?= e(addslashes($p['name'])) ?>')">
        🛒 Add to Cart
      </button>
      <a href="products.php" class="btn btn-outline">← Back</a>
    </div>

    <!-- Meta tags -->
    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px;">
      <div style="display: flex; gap: 12px; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
        <span>🏷️</span>
        <span>Brand: <strong style="color: var(--text)"><?= e($p['brand']) ?></strong></span>
      </div>
      <div style="display: flex; gap: 12px; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
        <span>✅</span>
        <span>Genuine product with warranty</span>
      </div>
      <div style="display: flex; gap: 12px; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
        <span>🚀</span>
        <span>Express delivery available</span>
      </div>
    </div>
  </div>
</div>

<!-- Related Products -->
<?php if ($related && $related->num_rows > 0): ?>
<section class="section" style="border-top: 1px solid var(--border);">
  <div class="section-header">
    <span class="section-label">More from <?= e($p['brand']) ?></span>
    <h2 class="section-title">Related Products</h2>
  </div>
  <div class="products-grid">
    <?php while ($rp = $related->fetch_assoc()): ?>
    <div class="product-card reveal"
         data-name="<?= e($rp['name']) ?>"
         data-brand="<?= e($rp['brand']) ?>"
         data-price="<?= $rp['price'] ?>"
         onclick="location.href='product.php?id=<?= $rp['id'] ?>'">
      <div class="product-card-img">
        <img src="<?= productImage($rp['image']) ?>" alt="<?= e($rp['name']) ?>" loading="lazy">
      </div>
      <div class="product-card-body">
        <div class="product-brand"><?= e($rp['brand']) ?></div>
        <div class="product-name"><?= e($rp['name']) ?></div>
        <div class="product-price-row">
          <div class="product-price"><?= price($rp['price']) ?></div>
          <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?= $rp['id'] ?>, '<?= e(addslashes($rp['name'])) ?>')" title="Add to cart">+</button>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</section>
<?php endif; ?>

<!-- Footer -->
<footer>
  <span class="footer-logo">Comii Lex</span>
  <span>© <?= date('Y') ?> Comii Lex. Crafted by Ravindu Jeewantha.</span>
  <span style="color: var(--text-dim);">Premium Tech · Sri Lanka</span>
</footer>

<script src="script.js"></script>
</body>
</html>
