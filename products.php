<?php
require_once 'config.php';

$db = getDB();

// Get filter params
$brandParam = isset($_GET['brand']) ? trim($_GET['brand']) : '';

// Get all brands for filter
$brands = $db->query("SELECT DISTINCT brand FROM products ORDER BY brand");
$brandList = [];
while ($b = $brands->fetch_assoc()) $brandList[] = $b['brand'];

// Fetch all products
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $db->query($sql);

$cartCount = cartCount();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products — Comii Lex</title>
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

<!-- Page Banner -->
<div class="page-banner">
  <div class="breadcrumb">
    <a href="index.php">Home</a>
    <span>/</span>
    <span style="color: var(--text)">Products</span>
  </div>
  <h1>All Products</h1>
  <p><?= $result ? $result->num_rows : 0 ?> items in our collection</p>
</div>

<!-- Filters -->
<div style="padding: clamp(24px, 4vw, 40px) clamp(16px, 4vw, 48px) 0;">
  <div class="filters-bar" style="padding: 0; margin-bottom: 20px;">
    <!-- Search -->
    <div class="search-wrap">
      <span class="search-icon">🔍</span>
      <input type="text" id="search-input" class="search-input" placeholder="Search products...">
    </div>

    <!-- Brand dropdown -->
    <select id="brand-filter" class="filter-select">
      <option value="">All Brands</option>
      <?php foreach ($brandList as $brand): ?>
      <option value="<?= e($brand) ?>" <?= $brandParam === $brand ? 'selected' : '' ?>><?= e($brand) ?></option>
      <?php endforeach; ?>
    </select>

    <!-- Sort -->
    <select id="sort-filter" class="filter-select">
      <option value="">Sort By</option>
      <option value="price-asc">Price: Low to High</option>
      <option value="price-desc">Price: High to Low</option>
    </select>
  </div>

  <!-- Brand Chips -->
  <div class="filter-chips" style="margin-bottom: 24px;">
    <button class="chip active" data-brand="">All</button>
    <?php foreach ($brandList as $brand): ?>
    <button class="chip" data-brand="<?= e($brand) ?>"><?= e($brand) ?></button>
    <?php endforeach; ?>
  </div>
</div>

<!-- Products Grid -->
<div style="padding: 0 clamp(16px, 4vw, 48px) clamp(48px, 6vw, 80px);">
  <div class="products-grid">
    <?php if ($result && $result->num_rows > 0):
      $i = 0;
      while ($p = $result->fetch_assoc()):
        $i++;
    ?>
    <div class="product-card reveal"
         id="card-<?= $p['id'] ?>"
         data-name="<?= e($p['name']) ?>"
         data-brand="<?= e($p['brand']) ?>"
         data-price="<?= $p['price'] ?>"
         onclick="location.href='product.php?id=<?= $p['id'] ?>'"
         style="animation-delay: <?= $i * 0.05 ?>s">
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
    <div class="empty-state" style="grid-column:1/-1">
      <div class="empty-icon">📦</div>
      <div class="empty-title">No Products Found</div>
      <p class="empty-sub">Import the SQL file to add products.</p>
    </div>
    <?php endif; ?>

    <!-- No results state (JS-controlled) -->
    <div id="no-results" class="no-results" style="display:none">
      <h3>No results found</h3>
      <p>Try adjusting your search or filters</p>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  <span class="footer-logo">Comii Lex</span>
  <span>© <?= date('Y') ?> Comii Lex. Crafted by Ravindu Jeewantha.</span>
  <span style="color: var(--text-dim);">Premium Tech · Sri Lanka</span>
</footer>

<script src="script.js"></script>
<script>
// Pre-select brand from URL param
document.addEventListener('DOMContentLoaded', () => {
  const urlBrand = '<?= e($brandParam) ?>';
  if (urlBrand) {
    const chip = document.querySelector(`.chip[data-brand="${urlBrand}"]`);
    if (chip) {
      document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
    }
    // Trigger filter
    const event = new Event('input');
    document.getElementById('search-input').dispatchEvent(event);
  }
});
</script>
</body>
</html>
