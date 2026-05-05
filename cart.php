<?php
require_once 'config.php';

$db = getDB();
$sid = session_id();

/* ─── AJAX Actions ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    switch ($action) {
        case 'add':
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            // Check product exists
            $check = $db->prepare("SELECT id FROM products WHERE id = ?");
            $check->bind_param('i', $productId);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            // Upsert
            $stmt = $db->prepare("
                INSERT INTO cart (session_id, product_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            $stmt->bind_param('siii', $sid, $productId, $qty, $qty);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'count' => cartCount()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            exit;

        case 'update':
            $delta = (int)($_POST['delta'] ?? 0);
            // Get current qty
            $stmt = $db->prepare("SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?");
            $stmt->bind_param('si', $sid, $productId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) { echo json_encode(['reload' => true]); exit; }

            $newQty = $row['quantity'] + $delta;
            if ($newQty <= 0) {
                // Remove
                $del = $db->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
                $del->bind_param('si', $sid, $productId);
                $del->execute();
                echo json_encode(['reload' => true]);
                exit;
            }

            $upd = $db->prepare("UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?");
            $upd->bind_param('isi', $newQty, $sid, $productId);
            $upd->execute();

            // Get new totals
            $priceQ = $db->prepare("SELECT price FROM products WHERE id = ?");
            $priceQ->bind_param('i', $productId);
            $priceQ->execute();
            $priceRow = $priceQ->get_result()->fetch_assoc();
            $unitPrice = $priceRow ? $priceRow['price'] : 0;
            $subtotal = number_format($unitPrice * $newQty, 2);

            // Total
            $totQ = $db->prepare("
                SELECT SUM(p.price * c.quantity) as total
                FROM cart c
                JOIN products p ON p.id = c.product_id
                WHERE c.session_id = ?
            ");
            $totQ->bind_param('s', $sid);
            $totQ->execute();
            $totRow = $totQ->get_result()->fetch_assoc();
            $total = number_format($totRow['total'] ?? 0, 2);

            echo json_encode(['reload' => false, 'qty' => $newQty, 'subtotal' => $subtotal, 'total' => $total]);
            exit;

        case 'remove':
            $del = $db->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
            $del->bind_param('si', $sid, $productId);
            $del->execute();
            echo json_encode(['success' => true]);
            exit;

        case 'count':
            echo json_encode(['count' => cartCount()]);
            exit;
    }
}

/* ─── Fetch Cart Items ─── */
$stmt = $db->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.brand, p.image
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.session_id = ?
    ORDER BY c.added_at DESC
");
$stmt->bind_param('s', $sid);
$stmt->execute();
$cartItems = $stmt->get_result();

$subtotal = 0;
$items = [];
while ($row = $cartItems->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['line_total'];
    $items[] = $row;
}

$shipping = count($items) > 0 ? 9.99 : 0;
$total = $subtotal + $shipping;
$cartCount = cartCount();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cart — Comii Lex</title>
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
    <li><a href="products.php">Products</a></li>
    <li><a href="cart.php" class="active">Cart</a></li>
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
  <a href="products.php">Products</a>
  <a href="cart.php" class="active">Cart</a>
</div>

<!-- Page Banner -->
<div class="page-banner">
  <div class="breadcrumb">
    <a href="index.php">Home</a>
    <span>/</span>
    <span style="color: var(--text)">Cart</span>
  </div>
  <h1>Your Cart</h1>
  <p><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in your cart</p>
</div>

<?php if (empty($items)): ?>
<!-- Empty Cart -->
<div id="empty-state" class="empty-state" style="min-height: 50vh">
  <div class="empty-icon">🛒</div>
  <div class="empty-title">Your cart is empty</div>
  <p class="empty-sub">Add some products to get started</p>
  <a href="products.php" class="btn btn-primary" style="margin-top: 8px">Browse Products →</a>
</div>

<?php else: ?>

<!-- Cart Content -->
<div id="cart-content">
  <div class="cart-layout">
    <!-- Items -->
    <div class="cart-items" id="cart-items-list">
      <?php foreach ($items as $item): ?>
      <div class="cart-item" id="item-<?= $item['product_id'] ?>">
        <!-- Image -->
        <img class="cart-item-img"
             src="<?= productImage($item['image']) ?>"
             alt="<?= e($item['name']) ?>">

        <!-- Info -->
        <div class="cart-item-info">
          <div class="cart-item-brand"><?= e($item['brand']) ?></div>
          <div class="cart-item-name">
            <a href="product.php?id=<?= $item['product_id'] ?>" style="color: var(--text)">
              <?= e($item['name']) ?>
            </a>
          </div>
          <div class="cart-item-controls">
            <!-- Qty Control -->
            <div class="qty-control">
              <button class="qty-btn" onclick="changeQty(<?= $item['product_id'] ?>, -1)">−</button>
              <span class="qty-val" id="qty-<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span>
              <button class="qty-btn" onclick="changeQty(<?= $item['product_id'] ?>, 1)">+</button>
            </div>
            <!-- Remove -->
            <button class="btn btn-danger btn-sm" onclick="removeItem(<?= $item['product_id'] ?>)">Remove</button>
          </div>
        </div>

        <!-- Price -->
        <div class="cart-item-price">
          <?= price($item['price']) ?>
          <div class="cart-item-subtotal" id="sub-<?= $item['product_id'] ?>">
            Subtotal: $<?= number_format($item['line_total'], 2) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Summary -->
    <div class="cart-summary">
      <div class="summary-title">Order Summary</div>

      <div class="summary-row">
        <span>Subtotal (<?= count($items) ?> items)</span>
        <span>$<?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="summary-row">
        <span>Shipping</span>
        <span>$<?= number_format($shipping, 2) ?></span>
      </div>
      <div class="summary-row">
        <span>Taxes</span>
        <span>Calculated at checkout</span>
      </div>
      <div class="summary-row total">
        <span>Total</span>
        <span id="cart-total">$<?= number_format($total, 2) ?></span>
      </div>

      <button class="btn btn-primary btn-full btn-lg checkout-btn" onclick="showToast('Checkout coming soon! 🚀')">
        Proceed to Checkout →
      </button>

      <a href="products.php" class="btn btn-ghost btn-full" style="margin-top: 8px; justify-content: center;">
        ← Continue Shopping
      </a>

      <!-- Trust badges -->
      <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 10px; align-items: center; font-size: 0.8rem; color: var(--text-muted);">
          <span>🔒</span> <span>Secure 256-bit SSL checkout</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; font-size: 0.8rem; color: var(--text-muted);">
          <span>↩️</span> <span>Free 30-day returns</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; font-size: 0.8rem; color: var(--text-muted);">
          <span>✅</span> <span>Genuine product guarantee</span>
        </div>
      </div>
    </div>
  </div>
</div>

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
