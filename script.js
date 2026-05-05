/* ===== COMII LEX — SCRIPT.JS ===== */

/* ─── Loading Screen ─── */
window.addEventListener('load', () => {
  setTimeout(() => {
    const loader = document.getElementById('loading-screen');
    if (loader) loader.classList.add('hidden');
  }, 1200);
});

/* ─── Theme Toggle ─── */
const theme = localStorage.getItem('cl-theme') || 'dark';
document.documentElement.setAttribute('data-theme', theme);

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('cl-theme', next);
  const btn = document.getElementById('theme-btn');
  if (btn) btn.textContent = next === 'dark' ? '☀️' : '🌙';
}

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('theme-btn');
  if (btn) btn.textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
});

/* ─── Hamburger Menu ─── */
function toggleMobileMenu() {
  const ham = document.getElementById('hamburger');
  const nav = document.getElementById('mobile-nav');
  if (ham && nav) {
    ham.classList.toggle('active');
    nav.classList.toggle('open');
  }
}

/* ─── Toast Notifications ─── */
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <div class="toast-icon">${type === 'success' ? '✓' : '✕'}</div>
    <span>${message}</span>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('out');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

/* ─── Cart Badge ─── */
function updateCartBadge() {
  fetch('cart.php?action=count')
    .then(r => r.json())
    .then(data => {
      const badge = document.getElementById('cart-badge');
      if (!badge) return;
      if (data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    })
    .catch(() => {});
}

/* ─── Add to Cart (AJAX) ─── */
function addToCart(productId, name) {
  const btn = event.currentTarget;
  btn.style.transform = 'scale(0.85) rotate(90deg)';

  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=add&product_id=${productId}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast(`${name} added to cart!`);
      updateCartBadge();
    } else {
      showToast(data.message || 'Something went wrong', 'error');
    }
    setTimeout(() => { btn.style.transform = ''; }, 400);
  })
  .catch(() => {
    showToast('Failed to add to cart', 'error');
    setTimeout(() => { btn.style.transform = ''; }, 400);
  });
}

/* ─── Product Filters ─── */
function initFilters() {
  const search = document.getElementById('search-input');
  const brandFilter = document.getElementById('brand-filter');
  const sortFilter = document.getElementById('sort-filter');
  const chips = document.querySelectorAll('.chip[data-brand]');

  function filterProducts() {
    const q = search ? search.value.toLowerCase() : '';
    const brand = brandFilter ? brandFilter.value : '';
    const sort = sortFilter ? sortFilter.value : '';
    const activeChip = document.querySelector('.chip[data-brand].active');
    const chipBrand = activeChip ? activeChip.dataset.brand : '';
    const effectiveBrand = chipBrand || brand;

    const cards = document.querySelectorAll('.product-card[data-name]');
    let visible = [];

    cards.forEach(card => {
      const name = card.dataset.name.toLowerCase();
      const cardBrand = card.dataset.brand.toLowerCase();
      const price = parseFloat(card.dataset.price);
      const matchQ = !q || name.includes(q) || cardBrand.includes(q);
      const matchBrand = !effectiveBrand || cardBrand === effectiveBrand.toLowerCase();

      if (matchQ && matchBrand) {
        card.style.display = '';
        visible.push({ card, price });
      } else {
        card.style.display = 'none';
      }
    });

    // Sort
    if (sort && visible.length > 0) {
      const grid = visible[0].card.parentElement;
      if (sort === 'price-asc') visible.sort((a, b) => a.price - b.price);
      if (sort === 'price-desc') visible.sort((a, b) => b.price - a.price);
      visible.forEach(({ card }) => grid.appendChild(card));
    }

    // No results
    const noResults = document.getElementById('no-results');
    if (noResults) noResults.style.display = visible.length === 0 ? 'block' : 'none';
  }

  if (search) search.addEventListener('input', filterProducts);
  if (brandFilter) brandFilter.addEventListener('change', filterProducts);
  if (sortFilter) sortFilter.addEventListener('change', filterProducts);

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('active'));
      chip.classList.toggle('active');
      if (brandFilter) brandFilter.value = '';
      filterProducts();
    });
  });
}

/* ─── Cart Quantity ─── */
function changeQty(productId, delta) {
  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update&product_id=${productId}&delta=${delta}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.reload) {
      location.reload();
    } else {
      const el = document.getElementById(`qty-${productId}`);
      const sub = document.getElementById(`sub-${productId}`);
      const totalEl = document.getElementById('cart-total');
      if (el) el.textContent = data.qty;
      if (sub) sub.textContent = `$${data.subtotal}`;
      if (totalEl) totalEl.textContent = `$${data.total}`;
    }
  })
  .catch(() => showToast('Update failed', 'error'));
}

function removeItem(productId) {
  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=remove&product_id=${productId}`
  })
  .then(() => {
    const item = document.getElementById(`item-${productId}`);
    if (item) {
      item.style.opacity = '0';
      item.style.transform = 'translateX(40px)';
      item.style.transition = 'all 0.3s ease';
      setTimeout(() => { item.remove(); updateTotal(); checkEmpty(); }, 300);
    }
  });
}

function updateTotal() {
  // Recalculate from DOM (fallback)
}

function checkEmpty() {
  const items = document.querySelectorAll('.cart-item');
  const emptyState = document.getElementById('empty-state');
  const cartContent = document.getElementById('cart-content');
  if (items.length === 0 && emptyState && cartContent) {
    emptyState.style.display = 'flex';
    cartContent.style.display = 'none';
  }
}

/* ─── Product Detail Qty ─── */
function changeDetailQty(delta) {
  const el = document.getElementById('detail-qty');
  if (!el) return;
  let val = parseInt(el.textContent) + delta;
  if (val < 1) val = 1;
  if (val > 99) val = 99;
  el.textContent = val;
}

function addDetailToCart(productId, name) {
  const qty = parseInt(document.getElementById('detail-qty')?.textContent || 1);
  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=add&product_id=${productId}&qty=${qty}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast(`${qty}× ${name} added to cart!`);
      updateCartBadge();
    }
  });
}

/* ─── Scroll Reveal ─── */
function initScrollReveal() {
  const elements = document.querySelectorAll('.reveal');
  if (!elements.length) return;
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        entry.target.style.transitionDelay = `${i * 0.05}s`;
        entry.target.classList.add('visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  elements.forEach(el => io.observe(el));
}

/* ─── 3D Card Tilt ─── */
function initCardTilt() {
  document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `perspective(800px) rotateX(${-y * 8}deg) rotateY(${x * 8}deg) translateY(-6px)`;
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });
}

/* ─── Stagger card animations ─── */
function initCardAnimations() {
  document.querySelectorAll('.product-card').forEach((card, i) => {
    card.style.animationDelay = `${i * 0.07}s`;
  });
}

/* ─── Init ─── */
document.addEventListener('DOMContentLoaded', () => {
  updateCartBadge();
  initFilters();
  initScrollReveal();
  initCardTilt();
  initCardAnimations();

  // Close mobile nav on link click
  document.querySelectorAll('.mobile-nav a').forEach(a => {
    a.addEventListener('click', () => {
      document.getElementById('hamburger')?.classList.remove('active');
      document.getElementById('mobile-nav')?.classList.remove('open');
    });
  });
});
