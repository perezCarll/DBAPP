// ===== Utils =====
const $ = (sel, ctx=document) => ctx.querySelector(sel);
const $$ = (sel, ctx=document) => [...ctx.querySelectorAll(sel)];

// ===== App State =====
let currentCurrency = 'PHP';
let role = 'Customer'; // demo-only; can be changed from the navbar dropdown
const flags = { PHP: '#3c3b6e', USD: '#0a3161', EUR: '#c60c30' };

// Persist demo state in localStorage
function loadState() {
  currentCurrency = localStorage.getItem('currency') || 'PHP';

  // Migrate old KRW selection to EUR (prevents ₩NaN if KRW was saved before)
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
function formatCurrency(val, code){
  const locale = { PHP: 'en-PH', USD: 'en-US', EUR: 'de-DE' }[code] || 'en-PH';
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: code,
    maximumFractionDigits: 0
  }).format(val);
}

// ===== Navbar controls =====
function initNavbar(){
  const currencyFlag = $('#currencyFlag');
  const currencyLabel = $('#currencyLabel');
  const roleLabel = $('#roleLabel');

  if(currencyFlag) currencyFlag.style.background = flags[currentCurrency];
  if(currencyLabel) currencyLabel.textContent = currentCurrency;
  if(roleLabel) roleLabel.textContent = role;

  $$('.currency-option').forEach(item => {
    item.addEventListener('click', (e)=>{
      e.preventDefault();
      currentCurrency = item.dataset.code;
      saveState();
      if(currencyFlag) currencyFlag.style.background = flags[currentCurrency];
      if(currencyLabel) currencyLabel.textContent = currentCurrency;
      document.dispatchEvent(new CustomEvent('currency:changed', { detail: currentCurrency }));
    });
  });

  $$('.role-option').forEach(item => {
    item.addEventListener('click', (e)=>{
      e.preventDefault();
      role = item.dataset.role;
      saveState();
      if(roleLabel) roleLabel.textContent = role;
      document.dispatchEvent(new CustomEvent('role:changed', { detail: role }));
    });
  });
}

// ===== Products Page Renderer =====
async function renderProducts(){
  const grid = $('#productGrid');
  if(!grid) return;

  const res = await fetch('/data/products.json');
  const items = await res.json();

  // Optional compatibility: if any item still has KRW, copy to EUR
  items.forEach(p => {
    if (p.prices && p.prices.KRW !== undefined && p.prices.EUR === undefined) {
      p.prices.EUR = p.prices.KRW;
      delete p.prices.KRW;
    }
  });

  const filterCategory = $('#filterCategory');
  const filterBranch = $('#filterBranch');

  function draw() {
    const cat = filterCategory?.value || 'all';
    const br = filterBranch?.value || 'all';
    grid.innerHTML = '';

    items.forEach(p => {
      const okCat = (cat==='all' || p.category===cat);
      const okBr = (br==='all' || p.branches.includes(br));
      if(!(okCat && okBr)) return;

      // Safe price fallback to avoid NaN
      const priceNow = (
        p.prices[currentCurrency] ??
        p.prices.PHP ??
        p.prices.USD ??
        0
      );

      const col = document.createElement('div');
      col.className = 'col-12 col-sm-6 col-lg-4';
      col.innerHTML = `
        <div class="card h-100 product" data-id="${p.id}">
          <img src="${p.image}" class="card-img-top" alt="${p.name}" />
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title mb-0">${p.name}</h5>
              <span class="badge text-bg-secondary">${p.branches.join(', ')}</span>
            </div>
            <p class="card-text text-muted">${p.description}</p>
            <div class="mt-auto d-flex align-items-center justify-content-between">
              <span class="price" data-price>${formatCurrency(priceNow, currentCurrency)}</span>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-success" data-action="quick-view">Details</button>
                <button class="btn btn-sm btn-success" data-action="add-to-cart"><i class="bi bi-bag-plus"></i></button>
              </div>
            </div>
          </div>
        </div>`;
      grid.appendChild(col);
    });
  }

  // Initial draw + listeners
  draw();
  filterCategory?.addEventListener('change', draw);
  filterBranch?.addEventListener('change', draw);
  document.addEventListener('currency:changed', () => { draw(); });

  grid.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-action]');
    if(!btn) return;
    const card = e.target.closest('.product');
    const id = card.dataset.id;
    const item = items.find(i => i.id===id);

    if(btn.dataset.action==='quick-view'){
      $('#quickViewTitle').textContent = item.name;
      $('#quickViewDesc').textContent = item.description;
      $('#quickViewImg').src = item.image;
      const quickPrice = item.prices[currentCurrency] ?? item.prices.PHP ?? 0;
      $('#quickViewPrice').textContent = formatCurrency(quickPrice, currentCurrency);
      $('#quickViewAdd').onclick = ()=> addToCart(item);
      const modal = new bootstrap.Modal('#quickView');
      modal.show();
    }
    if(btn.dataset.action==='add-to-cart'){
      addToCart(item);
    }
  });
}

// ===== Cart (demo only) =====
const cart = [];
function addToCart(item){
  const found = cart.find(i => i.id===item.id);
  if(found) found.qty += 1;
  else cart.push({ id:item.id, title:item.name, img:item.image, qty:1, prices:item.prices });
  updateCartUI();
  toast('Added to cart');
}
function updateCartUI(){
  const count = cart.reduce((a,b)=>a+b.qty,0);
  const badge = $('#cartCount');
  if(badge) badge.textContent = count;

  const ul = $('#cartItems');
  if(!ul) return;
  ul.innerHTML='';
  cart.forEach((item, idx)=>{
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex align-items-center justify-content-between';
    const priceNow = (item.prices[currentCurrency] ?? item.prices.PHP ?? 0) * item.qty;
    li.innerHTML = `
      <div class="d-flex align-items-center gap-3">
        <img src="${item.img}" alt="${item.title}" width="48" height="48" class="rounded object-fit-cover" />
        <div>
          <div class="fw-semibold">${item.title}</div>
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

  const subtotal = cart.reduce(
    (sum, i)=> sum + (i.prices[currentCurrency] ?? i.prices.PHP ?? 0) * i.qty,
    0
  );
  $('#cartSubtotal').textContent = count ? formatCurrency(subtotal, currentCurrency) : '—';
}
$('#cartItems')?.addEventListener('click', (e)=>{
  const btn = e.target.closest('button[data-op]');
  if(!btn) return;
  const i = Number(btn.dataset.idx);
  const op = btn.dataset.op;
  if(op==='inc') cart[i].qty += 1;
  if(op==='dec') cart[i].qty = Math.max(1, cart[i].qty - 1);
  if(op==='rm') cart.splice(i,1);
  updateCartUI();
});

// Recompute cart totals when currency changes
document.addEventListener('currency:changed', () => updateCartUI());

function toast(msg){
  const el = document.createElement('div');
  el.className = 'toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3';
  el.role = 'alert';
  el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class='bi bi-check2-circle me-2'></i>${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  document.body.appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 1500 }); t.show();
  el.addEventListener('hidden.bs.toast', ()=> el.remove());
}

// ===== Role-based UI demo =====
function applyRoleUI(){
  // simply disable admin-only links if not Admin/Manager/Staff
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
  initNavbar();
  applyRoleUI();
  updateCartUI();
  renderProducts();
});
