// ===== Utils =====
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

// ===== App State =====
let currentCurrency = 'PHP';
let role = 'Customer'; // UI-only label now; real role comes from PHP session in navbar
const flags = { PHP: '#3c3b6e', USD: '#0a3161', EUR: '#c60c30' };

// Persist demo state in localStorage (for currency mainly)
function loadState() {
  currentCurrency = localStorage.getItem('currency') || 'PHP';

  // Old KRW → EUR migration safeguard
  if (currentCurrency === 'KRW') {
    currentCurrency = 'EUR';
    localStorage.setItem('currency', 'EUR');
  }

  role = localStorage.getItem('role') || 'Customer';
}
function saveState() {
  localStorage.setItem('currency', currentCurrency);
  localStorage.setItem('role', role);
}

// Currency formatting
function formatCurrency(val, code) {
  const locale = { PHP: 'en-PH', USD: 'en-US', EUR: 'de-DE' }[code] || 'en-PH';
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: code,
    maximumFractionDigits: 0
  }).format(val);
}

// Base FX from PHP price -> other currencies (very simple demo rates)
// From your Currencies table: 1 USD = 59 PHP, 1 EUR = 68 PHP
const fxFromPhp = {
  PHP: 1,
  USD: 1 / 59,
  EUR: 1 / 68
};

function convertFromPhp(valuePhp, code) {
  const factor = fxFromPhp[code] ?? 1;
  return valuePhp * factor;
}

// ===== Navbar controls =====
function initNavbar() {
  const currencyFlag = $('#currencyFlag');
  const currencyLabel = $('#currencyLabel');
  const roleLabel = $('#roleLabel');

  if (currencyFlag) currencyFlag.style.background = flags[currentCurrency];
  if (currencyLabel) currencyLabel.textContent = currentCurrency;
  if (roleLabel) roleLabel.textContent = role;

  // Currency dropdown
  $$('.currency-option').forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      currentCurrency = item.dataset.code;
      saveState();
      if (currencyFlag) currencyFlag.style.background = flags[currentCurrency];
      if (currencyLabel) currencyLabel.textContent = currentCurrency;
      document.dispatchEvent(new CustomEvent('currency:changed', { detail: currentCurrency }));
    });
  });

  // Role label dropdown (UI only)
  $$('.role-option').forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      role = item.dataset.role;
      saveState();
      if (roleLabel) roleLabel.textContent = role;
      document.dispatchEvent(new CustomEvent('role:changed', { detail: role }));
    });
  });
}

// ===== Products Page Renderer (SQL-backed, no JSON fetch) =====
function renderProducts() {
  const grid = $('#productGrid');
  if (!grid) return; // only on products page

  const cards = $$('.product-card', grid);
  const filterCategory = $('#filterCategory');
  const filterBranch = $('#filterBranch');

  function applyFiltersAndPrices() {
    const cat = filterCategory?.value || 'all';
    const br = filterBranch?.value || 'all';

    cards.forEach(card => {
      const cardCat = card.dataset.category || 'all';
      const branchesAttr = card.dataset.branches || '';
      const branches = branchesAttr
        .split(',')
        .map(s => s.trim())
        .filter(Boolean);

      // Filter by category + branch
      const okCat = (cat === 'all' || cardCat === cat);
      const okBr = (br === 'all' || branches.includes(br));

      const col = card.closest('.col-12, .col-sm-6, .col-lg-4') || card.parentElement;
      if (col) col.style.display = (okCat && okBr) ? '' : 'none';

      // Update displayed price for current currency
      const priceEl = card.querySelector('.price-value');
      if (!priceEl) return;
      const basePhp = parseFloat(priceEl.dataset.pricePhp);
      if (Number.isNaN(basePhp)) return;

      const converted = convertFromPhp(basePhp, currentCurrency);
      priceEl.textContent = formatCurrency(converted, currentCurrency);
    });
  }

  // Initial render
  applyFiltersAndPrices();

  // Hook filters + currency change
  filterCategory?.addEventListener('change', applyFiltersAndPrices);
  filterBranch?.addEventListener('change', applyFiltersAndPrices);
  document.addEventListener('currency:changed', applyFiltersAndPrices);

  // Delegate buttons (Details + Add to cart)
  grid.addEventListener('click', (e) => {
    const card = e.target.closest('.product-card');
    if (!card) return;

    // Quick view
    if (e.target.closest('.btn-quick-view')) {
      const name = card.dataset.name;
      const desc = card.dataset.description;
      const img = card.dataset.img;
      const basePhp = parseFloat(card.dataset.pricePhp);
      const priceVal = convertFromPhp(basePhp, currentCurrency);

      const titleEl = $('#quickViewTitle');
      const descEl = $('#quickViewDesc');
      const imgEl = $('#quickViewImg');
      const priceEl = $('#quickViewPrice');
      const addBtn = $('#quickViewAdd');

      if (titleEl) titleEl.textContent = name;
      if (descEl) descEl.textContent = desc;
      if (imgEl) imgEl.src = img;
      if (priceEl) priceEl.textContent = formatCurrency(priceVal, currentCurrency);
      if (addBtn) {
        addBtn.onclick = () => addToCartFromCard(card);
      }

      if (window.bootstrap && $('#quickView')) {
        const modal = new bootstrap.Modal('#quickView');
        modal.show();
      }
    }

    // Add to cart (from card)
    if (e.target.closest('.btn-add-to-cart')) {
      addToCartFromCard(card);
    }
  });
}

// Build a cart item object from a product card's data attributes
function addToCartFromCard(card) {
  const basePhp = parseFloat(card.dataset.pricePhp);

  const item = {
    id: card.dataset.productId,
    name: card.dataset.name,
    image: card.dataset.img,
    qty: 1,
    prices: {
      PHP: basePhp,
      USD: convertFromPhp(basePhp, 'USD'),
      EUR: convertFromPhp(basePhp, 'EUR')
    }
  };
  addToCart(item);
}

// ===== Cart (with localStorage) =====
const CART_KEY = 'greenshop_cart_v1';
let cart = [];

function loadCart() {
  try {
    const raw = localStorage.getItem(CART_KEY);
    if (!raw) {
      cart = [];
      return;
    }
    const parsed = JSON.parse(raw);
    cart = Array.isArray(parsed) ? parsed : [];
  } catch (e) {
    cart = [];
  }
}

function saveCart() {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function addToCart(item) {
  const found = cart.find(i => i.id === item.id);
  if (found) {
    found.qty += item.qty ?? 1;
  } else {
    cart.push({ ...item, qty: item.qty ?? 1 });
  }
  saveCart();
  updateCartUI();
  renderCheckoutSummary();
  toast('Added to cart');
}

function updateCartUI() {
  const count = cart.reduce((a, b) => a + b.qty, 0);
  const badge = $('#cartCount');
  if (badge) badge.textContent = count;

  const ul = $('#cartItems');
  if (!ul) return; // cart offcanvas not on this page
  ul.innerHTML = '';

  if (cart.length === 0) {
    const li = document.createElement('li');
    li.className = 'list-group-item text-center text-muted';
    li.textContent = 'Your cart is empty.';
    ul.appendChild(li);
  } else {
    cart.forEach((item, idx) => {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex align-items-center justify-content-between';
      const priceNow = (item.prices[currentCurrency] ?? item.prices.PHP ?? 0) * item.qty;
      li.innerHTML = `
        <div class="d-flex align-items-center gap-3">
          <img src="${item.image}" alt="${item.name}" width="48" height="48" class="rounded object-fit-cover" />
          <div>
            <div class="fw-semibold">${item.name}</div>
            <small class="text-muted">Qty:
              <button class='btn btn-sm btn-outline-secondary py-0 px-2' data-idx='${idx}' data-op='dec'>−</button>
              <span class='mx-1'>${item.qty}</span>
              <button class='btn btn-sm btn-outline-secondary py-0 px-2' data-idx='${idx}' data-op='inc'>+</button>
            </small>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-semibold">${formatCurrency(priceNow, currentCurrency)}</div>
          <button class='btn btn-sm btn-link text-danger p-0' data-idx='${idx}' data-op='rm'>Remove</button>
        </div>`;
      ul.appendChild(li);
    });
  }

  const subtotal = cart.reduce(
    (sum, i) => sum + (i.prices[currentCurrency] ?? i.prices.PHP ?? 0) * i.qty,
    0
  );
  const subtotalEl = $('#cartSubtotal');
  if (subtotalEl) subtotalEl.textContent = count ? formatCurrency(subtotal, currentCurrency) : '—';
}

// Cart controls (offcanvas)
$('#cartItems')?.addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-op]');
  if (!btn) return;
  const i = Number(btn.dataset.idx);
  const op = btn.dataset.op;
  if (!cart[i]) return;

  if (op === 'inc') cart[i].qty += 1;
  if (op === 'dec') cart[i].qty = Math.max(1, cart[i].qty - 1);
  if (op === 'rm') cart.splice(i, 1);

  saveCart();
  updateCartUI();
  renderCheckoutSummary();
});

// ===== Checkout summary (checkout.php) =====
function renderCheckoutSummary() {
  const itemsEl = $('#checkoutItems');
  const countEl = $('#checkoutItemCount');
  const subtotalEl = $('#checkoutSubtotal');
  const shippingEl = $('#checkoutShipping');
  const totalEl = $('#checkoutTotal');
  const emptyEl = $('#checkoutEmpty');
  const fulfilmentSel = $('#checkoutFulfilment');
  const placeBtn = $('#checkoutPlaceOrder');

  if (!itemsEl || !countEl || !subtotalEl || !shippingEl || !totalEl) {
    return; // not on checkout page
  }

  itemsEl.innerHTML = '';
  const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);

  if (totalItems === 0) {
    if (emptyEl) {
      emptyEl.style.display = '';
      itemsEl.appendChild(emptyEl);
    }
    countEl.textContent = '0 items';
    subtotalEl.textContent = formatCurrency(0, currentCurrency);
    shippingEl.textContent = formatCurrency(0, currentCurrency);
    totalEl.textContent = formatCurrency(0, currentCurrency);
    if (placeBtn) placeBtn.disabled = true;
    return;
  }

  if (emptyEl) emptyEl.style.display = 'none';

  cart.forEach((item, idx) => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex align-items-center justify-content-between';
    const unitPrice = (item.prices[currentCurrency] ?? item.prices.PHP ?? 0);
    const lineTotal = unitPrice * item.qty;

    li.innerHTML = `
      <div class="d-flex align-items-center gap-3">
        <img src="${item.image}" alt="${item.name}" width="40" height="40"
             class="rounded object-fit-cover" />
        <div>
          <div class="fw-semibold">${item.name}</div>
          <small class="text-muted">
            Qty:
            <button class="btn btn-sm btn-outline-secondary py-0 px-2" data-idx="${idx}" data-op="dec">−</button>
            <span class="mx-1">${item.qty}</span>
            <button class="btn btn-sm btn-outline-secondary py-0 px-2" data-idx="${idx}" data-op="inc">+</button>
          </small>
        </div>
      </div>
      <div class="text-end">
        <div class="fw-semibold">${formatCurrency(lineTotal, currentCurrency)}</div>
        <button class="btn btn-sm btn-link text-danger p-0" data-idx="${idx}" data-op="rm">Remove</button>
      </div>
    `;
    itemsEl.appendChild(li);
  });

  const subtotal = cart.reduce(
    (sum, i) => sum + (i.prices[currentCurrency] ?? i.prices.PHP ?? 0) * i.qty,
    0
  );

  const fulfilment = fulfilmentSel?.value || 'pickup';
  // Simple shipping rule: delivery has flat 120 PHP, pickup = 0
  let shippingPhp = 0;
  if (fulfilment === 'delivery' && subtotal > 0) {
    shippingPhp = 120;
  }
  const shipping = convertFromPhp(shippingPhp, currentCurrency);
  const total = subtotal + shipping;

  countEl.textContent = `${totalItems} item${totalItems > 1 ? 's' : ''}`;
  subtotalEl.textContent = formatCurrency(subtotal, currentCurrency);
  shippingEl.textContent = formatCurrency(shipping, currentCurrency);
  totalEl.textContent = formatCurrency(total, currentCurrency);
  if (placeBtn) placeBtn.disabled = false;
}

// Checkout quantity / remove controls
$('#checkoutItems')?.addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-op]');
  if (!btn) return;
  const i = Number(btn.dataset.idx);
  const op = btn.dataset.op;
  if (!cart[i]) return;

  if (op === 'inc') cart[i].qty += 1;
  if (op === 'dec') cart[i].qty = Math.max(1, cart[i].qty - 1);
  if (op === 'rm') cart.splice(i, 1);

  saveCart();
  updateCartUI();
  renderCheckoutSummary();
});

// Recompute totals when fulfilment type changes
$('#checkoutFulfilment')?.addEventListener('change', renderCheckoutSummary);

// Recompute when currency changes
document.addEventListener('currency:changed', () => {
  updateCartUI();
  renderCheckoutSummary();
});

// ===== Toast helper =====
function toast(msg) {
  const el = document.createElement('div');
  el.className = 'toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3';
  el.role = 'alert';
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check2-circle me-2"></i>${msg}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  document.body.appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 1500 });
  t.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

// ===== Role-based UI demo =====
function applyRoleUI() {
  const adminLinks = $$('.requires-admin');
  adminLinks.forEach(link => {
    const allowed = (role === 'Admin' || role === 'Manager' || role === 'Staff');
    link.classList.toggle('disabled', !allowed);
    link.title = allowed ? '' : 'Login as Staff/Manager/Admin to access this (demo)';
  });
}
document.addEventListener('role:changed', applyRoleUI);

// ===== On load =====
document.addEventListener('DOMContentLoaded', () => {
  loadState();
  loadCart();
  initNavbar();
  applyRoleUI();
  updateCartUI();
  renderProducts();
  renderCheckoutSummary();

  // Attach cart JSON to checkout form on submit
  const checkoutForm = $('#checkoutForm');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', (e) => {
      if (!cart || cart.length === 0) {
        e.preventDefault();
        alert('Your cart is empty.');
        return;
      }

      const payloadItems = cart.map(item => ({
        product_id: Number(item.id),
        qty: Number(item.qty)
      }));

      const cartField = $('#checkoutCartJson');
      if (cartField) {
        cartField.value = JSON.stringify(payloadItems);
      }

      const curField = $('#checkoutCurrencyCode');
      if (curField) {
        curField.value = currentCurrency;
      }
    });
  }
});