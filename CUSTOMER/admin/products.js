/* ═══════════════════════════════════════════════════════════
   BEJEWELRY ADMIN — Products (MySQL only). Data from window.__PRODUCTS__.
═══════════════════════════════════════════════════════════ */

const D = window.__PRODUCTS__ || {};

/* ── STATE (from PHP) ── */
let state = {
  products: D.products || [],
  categories: D.categories || [],
  currentCat: D.currentCat || 'all',
  searchQuery: D.searchQuery || '',
  view: 'grid',
  page: D.page || 1,
  perPage: D.perPage || 9,
  totalPages: D.pages || 1,
  totalCount: D.total || 0,
};

function productsUrl() {
  const p = new URLSearchParams();
  if (state.page > 1) p.set('page', state.page);
  if (state.currentCat !== 'all') p.set('category', state.currentCat);
  if (state.searchQuery) p.set('search', state.searchQuery);
  return 'products.php?' + p.toString();
}

/* ── UTILS ── */
function toast(msg, type = '', duration = 2800) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = `toast on${type ? ' ' + type : ''}`;
  setTimeout(() => t.className = 'toast', duration);
}

function setLoading(on) {
  const el = document.getElementById('loadingBar');
  if (el) el.classList.toggle('active', on);
}

function formatCurrency(n) {
  return '₱' + Number(n).toLocaleString('en-PH');
}

function getCsrfToken() {
  const tokenInput = document.querySelector('input[name="csrf_token"]');
  return tokenInput ? tokenInput.value : '';
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

function stockBadge(stock) {
  if (stock === null || stock === undefined) return '';
  if (stock <= 0) return '<span class="badge b-nostock">Out of Stock</span>';
  if (stock <= 5) return '<span class="badge b-low">Low Stock</span>';
  return '<span class="badge b-instock">In Stock</span>';
}

function stockClass(stock) {
  if (stock <= 0) return 'low';
  if (stock <= 5) return 'low';
  return '';
}

/* ── LOAD USER ── */
function loadUser() {
  const data = D.user;
  const name = (data && data.name) || 'Admin';
  const initials = (name || '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
  const a = document.getElementById('sbAvatar');
  const u = document.getElementById('sbUsername');
  const r = document.getElementById('sbUserRole');
  if (a) a.textContent = initials;
  if (u) u.textContent = name;
  if (r) r.textContent = (data && data.role) || 'Role';
}

/* ── LOAD BADGES ── */
function loadBadges() {
  const b = D.badges || {};
  if (b.pending_orders) { const el = document.getElementById('badgeOrders'); if (el) el.textContent = b.pending_orders; }
  if (b.new_products) { const el = document.getElementById('badgeProducts'); if (el) el.textContent = b.new_products; }
  if (b.low_stock) { const el = document.getElementById('badgeInventory'); if (el) el.textContent = b.low_stock; }
  if (b.pending_reviews) { const el = document.getElementById('badgeReviews'); if (el) el.textContent = b.pending_reviews; }
}

/* ── LOAD CATEGORIES (from __PRODUCTS__) ── */
function loadCategories() {
  const tabsEl = document.getElementById('categoryTabs');
  if (!tabsEl) return;
  tabsEl.innerHTML = `<button class="ftab ${state.currentCat === 'all' ? 'on' : ''}" data-cat="all">All</button>`;
  (state.categories || []).forEach(cat => {
    const name = typeof cat === 'string' ? cat : (cat && cat.name);
    if (!name) return;
    const btn = document.createElement('button');
    btn.className = 'ftab' + (state.currentCat === name ? ' on' : '');
    btn.dataset.cat = name;
    btn.textContent = name;
    tabsEl.appendChild(btn);
  });
  tabsEl.querySelectorAll('.ftab').forEach(btn => {
    btn.addEventListener('click', () => {
      const cat = btn.dataset.cat || 'all';
      location.href = 'products.php?page=1&category=' + encodeURIComponent(cat) + (state.searchQuery ? '&search=' + encodeURIComponent(state.searchQuery) : '');
    });
  });
  const addSel = document.getElementById('add_category');
  const editSel = document.getElementById('edit_category');
  ['add_category', 'edit_category'].forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    sel.innerHTML = '<option value="">Select category…</option>';
    (state.categories || []).forEach(cat => {
      const name = typeof cat === 'string' ? cat : (cat && cat.name);
      if (!name) return;
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      sel.appendChild(opt);
    });
  });
}

/* ── RENDER PRODUCTS ── */
function loadProducts() {
  const grid = document.getElementById('productGrid');
  if (!grid) return;
  if (state.products.length === 0) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <span class="empty-icon">💎</span>
        <h3>No products found</h3>
        <p>Try a different search or category filter.</p>
        <button class="btn btn-primary btn-sm" style="margin:var(--s4) auto 0" onclick="openAddModal()">＋ Add First Product</button>
      </div>`;
    renderPagination();
    return;
  }
  grid.className = `prod-grid${state.view === 'list' ? ' list-view' : ''}`;
  grid.innerHTML = state.products.map(p => {
    const salePrice = Number(p.price || 0);
    const origPrice = Number(p.orig_price || 0);
    const isDiscounted = p.is_on_sale && origPrice > salePrice;
    const imgHtml = p.image_url
      ? `<img src="${escHtml(p.image_url)}" alt="${escHtml(p.name)}" loading="lazy">`
      : `<span class="img-fallback">💎</span>`;
    const featuredBadge = p.is_featured ? `<span class="badge b-featured pcard-featured">★ Featured</span>` : '';
    const saleBadge = p.is_on_sale ? `<span class="badge b-sale pcard-sale">Sale</span>` : '';
    const saleBtnLabel = p.is_on_sale ? '✖ Remove Sale' : '🏷 Mark Sale';
    const saleBtnClass = p.is_on_sale ? 'sale-on' : 'sale-off';
    return `
      <div class="pcard" data-id="${p.id}">
        <div class="pcard-img">
          ${imgHtml}
          <div class="pcard-stock">${stockBadge(p.stock)}</div>
          ${saleBadge}
          ${featuredBadge}
        </div>
        <div class="pcard-body">
          ${state.view === 'list' ? '<div class="pcard-main">' : ''}
          <div class="pcard-cat">${escHtml(p.category || '')}</div>
          <div class="pcard-name">${escHtml(p.name)}</div>
          <div class="pcard-sku">SKU: ${escHtml(p.sku || '—')}</div>
          ${state.view === 'list' ? '</div>' : ''}
          <div class="pcard-foot">
            <div class="pcard-price-wrap">
              <div class="pcard-price">${formatCurrency(salePrice)}</div>
              ${isDiscounted ? `<div class="pcard-orig">${formatCurrency(origPrice)}</div>` : ''}
            </div>
            <div class="pcard-stock-num ${stockClass(p.stock)}">${p.stock ?? 0} in stock</div>
          </div>
          <div class="pcard-actions">
            <button class="pcard-btn" onclick="openEditModal(${p.id})">✏ Edit</button>
            <button class="pcard-btn" onclick="viewProduct(${p.id})">👁 View</button>
            <button class="pcard-btn ${saleBtnClass}" onclick="toggleSale(${p.id})">${saleBtnLabel}</button>
            <button class="pcard-btn del" data-id="${p.id}" data-name="${escHtml(p.name)}" onclick="openDeleteModal(this)">🗑</button>
          </div>
        </div>
      </div>`;
  }).join('');
  renderPagination();
  const countEl = document.getElementById('productCount');
  if (countEl) countEl.textContent = `Manage your jewellery catalogue — ${state.totalCount} item${state.totalCount !== 1 ? 's' : ''}`;
}

/* ── PAGINATION ── */
function renderPagination() {
  const wrap = document.getElementById('pagination');
  const info = document.getElementById('pagInfo');
  const btns = document.getElementById('pagBtns');
  if (!wrap) return;
  if (state.totalPages <= 1) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'flex';
  const start = (state.page - 1) * state.perPage + 1;
  const end = Math.min(state.page * state.perPage, state.totalCount);
  if (info) info.textContent = `Showing ${start}–${end} of ${state.totalCount}`;
  if (!btns) return;
  btns.innerHTML = '';
  const base = 'products.php?category=' + encodeURIComponent(state.currentCat) + (state.searchQuery ? '&search=' + encodeURIComponent(state.searchQuery) : '');
  const prev = document.createElement('button');
  prev.className = 'pg'; prev.textContent = '‹';
  prev.disabled = state.page === 1;
  prev.onclick = () => { location.href = base + (state.page > 2 ? '&page=' + (state.page - 1) : ''); };
  btns.appendChild(prev);
  for (let i = 1; i <= state.totalPages; i++) {
    const pg = document.createElement('button');
    pg.className = 'pg' + (i === state.page ? ' active' : '');
    pg.textContent = i;
    pg.onclick = () => { location.href = base + '&page=' + i; };
    btns.appendChild(pg);
  }
  const next = document.createElement('button');
  next.className = 'pg'; next.textContent = '›';
  next.disabled = state.page === state.totalPages;
  next.onclick = () => { location.href = base + '&page=' + (state.page + 1); };
  btns.appendChild(next);
}

/* ── VIEW TOGGLE ── */
function setView(v) {
  state.view = v;
  document.getElementById('gridViewBtn').classList.toggle('on', v === 'grid');
  document.getElementById('listViewBtn').classList.toggle('on', v === 'list');
  loadProducts();
}

/* ── SEARCH ── */
let searchTimeout;
const productSearchEl = document.getElementById('productSearch');
if (productSearchEl) {
  productSearchEl.value = state.searchQuery;
  productSearchEl.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      location.href = 'products.php?page=1&category=' + encodeURIComponent(state.currentCat) + '&search=' + encodeURIComponent(productSearchEl.value.trim());
    }
  });
  productSearchEl.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      const q = productSearchEl.value.trim();
      if (q.length < 2) return;
      location.href = 'products.php?page=1&category=' + encodeURIComponent(state.currentCat) + '&search=' + encodeURIComponent(q);
    }, 400);
  });
}

/* ── MODAL HELPERS ── */
function openModal(id) { const el = document.getElementById(id); if (el) el.classList.add('on'); }
function closeModal(id) { const el = document.getElementById(id); if (el) el.classList.remove('on'); }
document.querySelectorAll('.modal-bg').forEach(m =>
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('on'); })
);

/* ── ADD PRODUCT ── */
function openAddModal() {
  ['add_name','add_sku','add_material','add_desc'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  const priceEl = document.getElementById('add_price'); if (priceEl) priceEl.value = '';
  const stockEl = document.getElementById('add_stock'); if (stockEl) stockEl.value = '';
  const catEl = document.getElementById('add_category'); if (catEl) catEl.value = '';
  const prevEl = document.getElementById('addPreview'); if (prevEl) prevEl.innerHTML = '';
  const imgEl = document.getElementById('add_image'); if (imgEl) imgEl.value = '';
  openModal('addModal');
}

function handleAddProduct() {
  const name = document.getElementById('add_name') && document.getElementById('add_name').value.trim();
  const price = document.getElementById('add_price') && document.getElementById('add_price').value;
  if (!name || !price) { toast('Please fill in required fields.', 'error'); return; }
  const form = document.getElementById('addProductForm');
  if (!form) { toast('Add form not found.', 'error'); return; }
  form.submit();
}

function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  if (!preview) return;
  preview.innerHTML = '';

  const file = input && input.files && input.files[0] ? input.files[0] : null;
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function (e) {
    const img = document.createElement('img');
    img.className = 'upload-thumb';
    img.src = (e && e.target && e.target.result) ? e.target.result : '';
    preview.appendChild(img);
  };
  reader.readAsDataURL(file);
}

/* ── EDIT PRODUCT ── */
function openEditModal(productId) {
  const p = state.products.find(x => x.id == productId);
  openModal('editModal');
  document.getElementById('editModalTitle').textContent = p ? `Edit — ${p.name}` : 'Edit Product';
  const prevEl = document.getElementById('editPreview'); if (prevEl) prevEl.innerHTML = '';
  if (!p) { toast('Product not found.', 'error'); return; }
  document.getElementById('edit_id').value = p.id;
  document.getElementById('edit_name').value = p.name || '';
  document.getElementById('edit_sku').value = p.sku || '';
  document.getElementById('edit_material').value = p.material || '';
  document.getElementById('edit_price').value = p.price || '';
  document.getElementById('edit_stock').value = p.stock ?? '';
  document.getElementById('edit_desc').value = p.description || '';
  document.getElementById('edit_category').value = p.category || '';
  if (p.image_url) {
    const img = document.createElement('img');
    img.className = 'upload-thumb';
    img.src = p.image_url;
    prevEl.appendChild(img);
  }
}

function handleEditProduct() {
  const id = document.getElementById('edit_id') && document.getElementById('edit_id').value;
  const name = document.getElementById('edit_name') && document.getElementById('edit_name').value.trim();
  const price = document.getElementById('edit_price') && document.getElementById('edit_price').value;
  if (!id || !name || !price) { toast('Please fill in required fields.', 'error'); return; }
  const form = document.getElementById('editProductForm');
  if (!form) { toast('Edit form not found.', 'error'); return; }
  form.submit();
}

/* ── DELETE PRODUCT ── */
function openDeleteModal(btn) {
  if (!btn) return;
  document.getElementById('delete_id').value = btn.dataset.id || '';
  document.getElementById('deleteProductName').textContent = btn.dataset.name || '';
  openModal('deleteModal');
}

function handleDeleteProduct() {
  const id = document.getElementById('delete_id') && document.getElementById('delete_id').value;
  if (!id) return;
  const form = document.createElement('form');
  form.method = 'post';
  form.action = 'products_action.php';
  form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + escHtml(id) + '"><input type="hidden" name="csrf_token" value="' + escHtml(getCsrfToken()) + '">';
  document.body.appendChild(form);
  form.submit();
}

function postSaleAction(productId, enableSale, salePrice = null) {
  const form = document.createElement('form');
  form.method = 'post';
  form.action = 'products_action.php' + window.location.search;
  const fields = [
    '<input type="hidden" name="action" value="toggle_sale">',
    `<input type="hidden" name="id" value="${escHtml(String(productId))}">`,
    `<input type="hidden" name="sale_state" value="${enableSale ? '1' : '0'}">`,
    `<input type="hidden" name="csrf_token" value="${escHtml(getCsrfToken())}">`
  ];
  if (enableSale && salePrice !== null) {
    fields.push(`<input type="hidden" name="sale_price" value="${escHtml(String(salePrice))}">`);
  }
  form.innerHTML = fields.join('');
  document.body.appendChild(form);
  form.submit();
}

function applySaleDiscount(percent) {
  const currentText = document.getElementById('sale_current_price')?.textContent || '';
  const current = Number(currentText.replace(/[^0-9.]/g, ''));
  if (!Number.isFinite(current) || current <= 0) return;
  const saleValue = Math.max(1, Number((current * (1 - percent / 100)).toFixed(2)));
  const input = document.getElementById('sale_new_price');
  if (input) input.value = String(saleValue);
  updateSalePreview();
}

function updateSalePreview() {
  const curText = document.getElementById('sale_current_price')?.textContent || '';
  const current = Number(curText.replace(/[^0-9.]/g, ''));
  const sale = Number(document.getElementById('sale_new_price')?.value || 0);
  const preview = document.getElementById('sale_preview');
  const previewPrice = document.getElementById('sale_preview_price');
  const previewPct = document.getElementById('sale_preview_pct');
  if (!preview) return;

  if (!Number.isFinite(sale) || sale <= 0) {
    if (previewPrice) previewPrice.textContent = 'Enter a sale price to see preview.';
    if (previewPct) previewPct.textContent = '';
    return;
  }
  if (sale >= current) {
    if (previewPrice) previewPrice.innerHTML = '<strong>Invalid:</strong> sale price must be lower than current price.';
    if (previewPct) previewPct.textContent = '';
    return;
  }

  const save = current - sale;
  const pct = (save / current) * 100;
  if (previewPrice) previewPrice.innerHTML = `Now: <strong>${formatCurrency(sale)}</strong> · Was ${formatCurrency(current)} · Save ${formatCurrency(save)}`;
  if (previewPct) previewPct.textContent = `${pct.toFixed(1)}% off`;
}

function openSaleModal(productId) {
  const p = state.products.find(x => x.id == productId);
  if (!p) {
    toast('Product not found.', 'error');
    return;
  }

  const nameEl = document.getElementById('sale_product_name');
  const idEl = document.getElementById('sale_product_id');
  const curEl = document.getElementById('sale_current_price');
  const priceEl = document.getElementById('sale_new_price');

  if (nameEl) nameEl.value = p.name || '';
  if (idEl) idEl.value = String(p.id);
  if (curEl) curEl.textContent = formatCurrency(Number(p.price || 0));
  if (priceEl) {
    const suggested = Number(p.price || 0) > 0 ? Math.max(1, Math.floor(Number(p.price) * 0.6)) : '';
    priceEl.value = String(suggested);
  }
  updateSalePreview();
  openModal('saleModal');
}

function submitSalePrice() {
  const id = Number(document.getElementById('sale_product_id')?.value || 0);
  const current = Number((document.getElementById('sale_current_price')?.textContent || '').replace(/[^0-9.]/g, ''));
  const sale = Number(document.getElementById('sale_new_price')?.value || 0);

  if (!id || !Number.isFinite(sale) || sale <= 0) {
    toast('Please enter a valid sale price.', 'error');
    return;
  }
  if (sale >= current) {
    toast('Sale price must be lower than current price.', 'error');
    return;
  }

  closeModal('saleModal');
  postSaleAction(id, true, sale);
}

function openRemoveSaleModal(productId) {
  const p = state.products.find(x => x.id == productId);
  if (!p) {
    toast('Product not found.', 'error');
    return;
  }

  const idEl = document.getElementById('remove_sale_product_id');
  const nameEl = document.getElementById('remove_sale_product_name');
  const currentEl = document.getElementById('remove_sale_current_price');
  const restoredEl = document.getElementById('remove_sale_restored_price');

  const currentPrice = Number(p.price || 0);
  const restoredPrice = Number(p.orig_price || 0) > currentPrice ? Number(p.orig_price || 0) : currentPrice;

  if (idEl) idEl.value = String(p.id);
  if (nameEl) nameEl.textContent = p.name || 'Product';
  if (currentEl) currentEl.textContent = formatCurrency(currentPrice);
  if (restoredEl) restoredEl.textContent = formatCurrency(restoredPrice);

  openModal('removeSaleModal');
}

function submitRemoveSale() {
  const id = Number(document.getElementById('remove_sale_product_id')?.value || 0);
  if (!id) {
    toast('Product not found.', 'error');
    return;
  }
  closeModal('removeSaleModal');
  postSaleAction(id, false);
}

/* ── TOGGLE SALE ── */
function toggleSale(productId) {
  const p = state.products.find(x => x.id == productId);
  if (!p) {
    toast('Product not found.', 'error');
    return;
  }

  const enableSale = !p.is_on_sale;
  if (enableSale) {
    openSaleModal(productId);
    return;
  }

  openRemoveSaleModal(productId);
}

/* ── VIEW PRODUCT (modal) ── */
function viewProduct(id) {
  const p = state.products.find(x => x.id == id);
  if (!p) {
    toast('Product not found.', 'error');
    return;
  }

  const imgWrap = document.getElementById('viewProductImg');
  if (imgWrap) {
    imgWrap.innerHTML = '';
    if (p.image_url) {
      const img = document.createElement('img');
      img.src = p.image_url;
      img.alt = p.name || '';
      img.loading = 'lazy';
      imgWrap.appendChild(img);
    } else {
      imgWrap.textContent = '💎';
    }
  }

  const setText = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  };

  setText('viewCat', (p.category || '').toUpperCase());
  setText('viewName', p.name || '—');

  const stockBadgeEl = document.getElementById('viewStockBadge');
  if (stockBadgeEl) stockBadgeEl.innerHTML = stockBadge(p.stock);

  setText('viewFeatured', p.is_featured ? 'Featured product' : '');
  setText('viewSku', p.sku || '—');
  setText('viewMat', p.material || '—');
  setText('viewPrice', formatCurrency(p.price));
  setText('viewStock', (p.stock ?? 0) + ' in stock');
  setText('viewDesc', p.description || 'No description provided.');

  const editBtn = document.getElementById('viewEditBtn');
  if (editBtn) {
    editBtn.onclick = () => {
      closeModal('viewModal');
      openEditModal(p.id);
    };
  }

  openModal('viewModal');
}

/* ── EXPORT ── */
function handleExport() {
  location.href = 'reports.php';
}

/* ── LOGOUT ── */
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', () => { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}

/* ── NOTIFICATIONS ── */
function handleNotifications() {
  location.href = 'notifications.php';
}

/* ── GLOBAL SEARCH ── */
let globalSearchTimeout;
const globalSearchEl = document.getElementById('globalSearch');
if (globalSearchEl) {
  globalSearchEl.addEventListener('input', e => {
    clearTimeout(globalSearchTimeout);
    const q = e.target.value.trim();
    if (q.length < 2) return;
    globalSearchTimeout = setTimeout(() => {
      window.location.href = 'search.php?q=' + encodeURIComponent(q);
    }, 500);
  });
}

/* ── INIT ── */
function loadPage() {
  loadUser();
  loadBadges();
  loadCategories();
  loadProducts();
}

document.addEventListener('DOMContentLoaded', loadPage);
