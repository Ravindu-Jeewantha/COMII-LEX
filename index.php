r<?php
require_once 'config.php';

// Fetch featured products (latest 8)
$db = getDB();
$featured = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 8");
$brands = $db->query("SELECT DISTINCT brand FROM products ORDER BY brand");
$brandList = [];
while ($b = $brands->fetch_assoc()) $brandList[] = $b['brand'];

$cartCount = cartCount();
$currentPage = 'index';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comii Lex — Premium Tech Store</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body>

<!-- Loading Screen -->
<div id="loading-screen">
  <div class="loader-logo">Comii Lex</div>
  <div class="loader-bar"><div class="loader-bar-fill"></div></div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">Comii Lex</a>
  <ul class="nav-links">
    <li><a href="index.php" class="active">Home</a></li>
    <li><a href="products.php">Products</a></li>
    <li><a href="cart.php">Cart</a></li>
  </ul>
  <div class="nav-actions">
    <button class="theme-toggle" id="theme-btn" onclick="toggleTheme()" title="Toggle theme">☀️</button>
    <a href="cart.php" class="cart-icon">
      🛒
      <span class="cart-badge" id="cart-badge" style="<?= $cartCount === 0 ? 'display:none' : '' ?>"><?= $cartCount ?></span>
    </a>
    <div class="hamburger" id="hamburger" onclick="toggleMobileMenu()">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<!-- Mobile Nav -->
<div class="mobile-nav" id="mobile-nav">
  <a href="index.php" class="active">Home</a>
  <a href="products.php">Products</a>
  <a href="cart.php">Cart</a>
</div>

<!-- Hero -->
<section class="hero">
  <div class="hero-bg">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-grid"></div>
  </div>
  <div class="hero-content">
    <div class="hero-badge">
      <span></span>
      New arrivals just dropped
    </div>
    <h1>The Future of<br><span class="accent">Premium Tech</span></h1>
    <p>Discover the world's finest consumer electronics. Curated for those who demand nothing but the best in performance and design.</p>
    <div class="hero-cta">
      <a href="products.php" class="btn btn-primary btn-lg">Shop Collection →</a>
      <a href="#featured" class="btn btn-outline btn-lg">Featured Products</a>
    </div>
    <div class="hero-stats">
      <div class="stat-item">
        <span class="stat-num">500+</span>
        <span class="stat-label">Products</span>
      </div>
      <div class="stat-item">
        <span class="stat-num">50K+</span>
        <span class="stat-label">Happy Customers</span>
      </div>
      <div class="stat-item">
        <span class="stat-num">24/7</span>
        <span class="stat-label">Support</span>
      </div>
    </div>
  </div>
</section>

<!-- Features Strip -->
<div class="features-strip" style="margin-bottom: 0;">
  <div class="feature-item">
    <div class="feature-icon">🚀</div>
    <div class="feature-text">
      <strong>Express Delivery</strong>
      <span>Next-day nationwide</span>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">🔒</div>
    <div class="feature-text">
      <strong>Secure Payments</strong>
      <span>256-bit SSL encrypted</span>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">↩️</div>
    <div class="feature-text">
      <strong>Easy Returns</strong>
      <span>30-day hassle-free</span>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">✅</div>
    <div class="feature-text">
      <strong>Genuine Products</strong>
      <span>100% authentic guaranteed</span>
    </div>
  </div>
</div>

<!-- Featured Products -->
<section class="section" id="featured">
  <div class="section-header section-header-row">
    <div>
      <span class="section-label">Handpicked For You</span>
      <h2 class="section-title">Featured Products</h2>
      <p class="section-sub">The latest and greatest from top brands</p>
    </div>
    <a href="products.php" class="btn btn-outline">View All →</a>
  </div>

  <div class="products-grid">
    <?php if ($featured && $featured->num_rows > 0):
      while ($p = $featured->fetch_assoc()): ?>
      <div class="product-card reveal"
           data-name="<?= e($p['name']) ?>"
           data-brand="<?= e($p['brand']) ?>"
           data-price="<?= e($p['price']) ?>"
           onclick="location.href='product.php?id=<?= $p['id'] ?>'">
        <div class="product-card-img">
          <img src="<?= productImage($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        </div>
        <div class="product-card-body">
          <div class="product-brand"><?= e($p['brand']) ?></div>
          <div class="product-name"><?= e($p['name']) ?></div>
          <div class="product-price-row">
            <div class="product-price"><?= price($p['price']) ?></div>
            <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?= $p['id'] ?>, '<?= e(addslashes($p['name'])) ?>')" title="Add to cart">+</button>
          </div>
        </div>
      </div>
    <?php endwhile; else: ?>
      <div class="no-results" style="grid-column:1/-1; padding:60px 0; text-align:center; color:var(--text-muted)">
        <p>No products found. <a href="comii_lex.sql" style="color:var(--accent)">Import the SQL database</a> to get started.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Brand Showcase -->
<?php if (!empty($brandList)): ?>
<section style="padding: 0 clamp(16px, 4vw, 48px) 80px;">
  <div class="section-header" style="margin-bottom: 32px;">
    <span class="section-label">Brands We Carry</span>
    <h2 class="section-title">Top Brands</h2>
  </div>
  <div style="display: flex; flex-wrap: wrap; gap: 12px;">
    <?php foreach ($brandList as $brand): ?>
    <a href="products.php?brand=<?= urlencode($brand) ?>" class="chip"><?= e($brand) ?></a>
    <?php endforeach; ?>
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
