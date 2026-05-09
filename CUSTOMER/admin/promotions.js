/* BEJEWELRY — Promotions (MySQL only). Data from window.__PROMOTIONS__. */

const D = window.__PROMOTIONS__ || {};
let allPromos = D.promos || [];
let activeFilter = 'all';

function toast(msg, type) {
  const t = document.getElementById('toast');
  if (t) { t.textContent = msg; t.className = 'toast on' + (type ? ' ' + type : ''); setTimeout(() => t.className = 'toast', 2800); }
}
function setLoading(on) {
  const el = document.getElementById('loadingBar');
  if (el) el.classList.toggle('active', on);
}
function fmt(n) {
  return '₱' + Number(n || 0).toLocaleString('en-PH');
}
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

function promoActionCandidates() {
  return [
    'promotions_action.php',
    '../admin/promotions_action.php',
    '/CUSTOMER/admin/promotions_action.php',
    '/BEJEWELRY/CUSTOMER/admin/promotions_action.php',
  ];
}

async function promoActionRequest(payload) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const headers = { 'Content-Type': 'application/json' };
  if (csrf) headers['X-CSRF-Token'] = csrf;

  let lastError = new Error('Promotion action endpoint is not reachable');
  for (const url of promoActionCandidates()) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });

      let data = {};
      try { data = await res.json(); } catch (_) {}

      if (!res.ok) {
        const msg = data && data.error ? data.error : 'Request failed';
        throw new Error(msg);
      }

      return data;
    } catch (err) {
      lastError = err instanceof Error ? err : new Error(String(err));
    }
  }

  throw lastError;
}

function upsertPromoInMemory(promo) {
  if (!promo || !promo.id) return;
  const idx = allPromos.findIndex(x => Number(x.id) === Number(promo.id));
  if (idx >= 0) allPromos[idx] = promo;
  else allPromos.unshift(promo);
}
function clearSkel(el) {
  if (!el) return;
  el.classList.remove('skel', 'skel-text', 'skel-val');
  el.textContent = '';
}

function loadUser() {
  const data = D.user;
  const name = (data && data.name) || 'Admin';
  const initials = (name || '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
  const a = document.getElementById('sbAvatar'); const u = document.getElementById('sbUsername'); const r = document.getElementById('sbUserRole');
  if (a) a.textContent = initials; if (u) u.textContent = name; if (r) r.textContent = (data && data.role) || '—';
}
function loadBadges() {
  const b = D.badges || {};
  if (b.pending_orders) { const e = document.getElementById('badgeOrders'); if (e) e.textContent = b.pending_orders; }
  if (b.new_products) { const e = document.getElementById('badgeProducts'); if (e) e.textContent = b.new_products; }
  if (b.low_stock) { const e = document.getElementById('badgeInventory'); if (e) e.textContent = b.low_stock; }
  if (b.pending_reviews) { const e = document.getElementById('badgeReviews'); if (e) e.textContent = b.pending_reviews; }
}
function loadStats() {
  const s = D.stats || {};
  clearSkel(document.getElementById('valActive'));
  const valActive = document.getElementById('valActive'); if (valActive) valActive.textContent = s.active_count ?? '—';
  clearSkel(document.getElementById('subActive'));
  const subActive = document.getElementById('subActive'); if (subActive) subActive.textContent = (s.codes_expiring_soon ?? 0) + ' expiring soon';
  clearSkel(document.getElementById('valRedemptions'));
  const valR = document.getElementById('valRedemptions'); if (valR) valR.textContent = s.total_redemptions ?? '—';
  clearSkel(document.getElementById('subRedemptions'));
  const subR = document.getElementById('subRedemptions'); if (subR) subR.textContent = (s.redemptions_this_month ?? 0) + ' this month';
  clearSkel(document.getElementById('valDiscounts'));
  const valD = document.getElementById('valDiscounts'); if (valD) valD.textContent = fmt(s.total_discounts_given);
  clearSkel(document.getElementById('subDiscounts'));
  const subD = document.getElementById('subDiscounts'); if (subD) subD.textContent = 'Total value discounted';
  clearSkel(document.getElementById('valAvgRate'));
  const valAvg = document.getElementById('valAvgRate'); if (valAvg) valAvg.textContent = s.avg_discount_rate != null ? s.avg_discount_rate + '%' : '—';
  clearSkel(document.getElementById('subAvgRate'));
  const subAvg = document.getElementById('subAvgRate'); if (subAvg) subAvg.textContent = 'Across all active codes';
}
function loadPromos() {
  allPromos = D.promos || [];
  renderPromos();
}

/* ── RENDER PROMO GRID ── */
function renderPromos() {
  const grid = document.getElementById('promoGrid');
  const q    = document.getElementById('promoSearch').value.trim().toLowerCase();

  const list = allPromos.filter(p => {
    const matchFilter = activeFilter === 'all' || p.status === activeFilter;
    const matchSearch = !q || p.code.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q);
    return matchFilter && matchSearch;
  });

  grid.innerHTML = '';

  if (list.length === 0) {
    grid.innerHTML = `<div class="empty-state">
      <span class="empty-icon">🏷️</span>
      <h3>No promotions found</h3>
      <p>${q ? 'Try a different search term.' : 'Create your first promo code to get started.'}</p>
    </div>`;
  } else {
    list.forEach(p => grid.insertAdjacentHTML('beforeend', buildPromoCard(p)));
  }

  // Always show the "add new" card at the end
  grid.insertAdjacentHTML('beforeend', `
    <div class="promo-card-new" onclick="openCreateModal()">
      <div class="pcn-icon">+</div>
      <div class="pcn-label">Create New Promo</div>
      <div class="pcn-sub">Set up a discount code or campaign</div>
    </div>`);
}

function buildPromoCard(p) {
  const isExpired = p.status === 'expired';
  const isGold    = p.discount_type === 'fixed' || parseFloat(p.discount_value) >= 30;
  const pct = p.usage_limit > 0
    ? Math.min(100, Math.round((p.times_used / p.usage_limit) * 100))
    : null;

  const discountLabel = p.discount_type === 'percentage'
    ? `${p.discount_value}% off`
    : p.discount_type === 'fixed'
    ? `₱${p.discount_value} off`
    : 'Free shipping';

  const statusClass = { active:'b-active', expired:'b-expired', scheduled:'b-scheduled', paused:'b-paused' }[p.status] || 'b-expired';
  const cardClass   = ['promo-card', isExpired ? 'expired' : '', isGold ? 'gold-type' : ''].filter(Boolean).join(' ');

  const metaRows = [
    `<span>📅 ${escHtml(p.start_date)}${p.end_date ? ' – ' + escHtml(p.end_date) : ' · No expiry'}</span>`,
    `<span>💰 Min. order: ${p.min_order > 0 ? fmt(p.min_order) : 'None'}</span>`,
    `<span>📦 Applies to: ${escHtml(p.apply_to || 'All Products')}</span>`,
  ].join('');

  const statsHtml = `
    <div class="promo-stats">
      <div class="ps-item">
        <div class="ps-val">${p.times_used ?? 0}</div>
        <div class="ps-lbl">Used</div>
      </div>
      <div class="ps-item">
        <div class="ps-val">${p.usage_limit > 0 ? Math.max(0, p.usage_limit - (p.times_used ?? 0)) : '∞'}</div>
        <div class="ps-lbl">Remaining</div>
      </div>
      <div class="ps-item">
        <div class="ps-val">${fmt(p.total_saved)}</div>
        <div class="ps-lbl">Saved</div>
      </div>
    </div>`;

  const progressHtml = pct !== null ? `
    <div class="promo-progress">
      <div class="prog-label">
        <span>Usage</span>
        <span>${pct}%</span>
      </div>
      <div class="prog-track">
        <div class="prog-fill" style="width:${pct}%"></div>
      </div>
    </div>` : '';

  const actionsHtml = isExpired
    ? `<button class="btn btn-ghost btn-sm" onclick="duplicatePromo(${p.id})">⧉ Duplicate</button>`
    : `<button class="btn btn-ghost btn-sm" onclick="openEditModal(${p.id})">Edit</button>
       <button class="btn btn-danger btn-sm" data-id="${p.id}" data-code="${escHtml(p.code)}" onclick="confirmDeactivate(this)">Deactivate</button>
       <button class="btn btn-danger btn-sm" data-id="${p.id}" data-code="${escHtml(p.code)}" onclick="confirmDelete(this)">Delete</button>`;

  return `
    <div class="${cardClass}" id="promoCard-${p.id}">
      <div class="promo-card-stripe"></div>
      <div class="promo-card-body">
        <div class="promo-card-top">
          <span class="promo-code">${escHtml(p.code)}</span>
          <span class="badge ${statusClass}">${escHtml(p.status)}</span>
        </div>
        <div class="promo-desc">${escHtml(discountLabel)}${p.description ? ' — ' + escHtml(p.description) : ''}</div>
        <div class="promo-meta">${metaRows}</div>
        ${statsHtml}
        ${progressHtml}
        <div class="promo-actions">${actionsHtml}</div>
      </div>
    </div>`;
}

/* ── FILTER ── */
function setFilter(el, val) {
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  activeFilter = val;
  renderPromos();
}

function filterPromos() { renderPromos(); }

/* ── CREATE MODAL ── */
function openCreateModal() {
  document.getElementById('editPromoId').value             = '';
  document.getElementById('modalTitle').textContent        = 'Create Promotion';
  document.getElementById('modalSubtitle').textContent     = 'Fill in the details for your new promo code';
  document.getElementById('modalSaveBtn').textContent      = 'Create Promotion';
  clearForm();
  openModal('promoModal');
}

/* ── EDIT MODAL ── */
function openEditModal(id) {
  const p = allPromos.find(x => x.id == id);
  if (!p) return;

  document.getElementById('editPromoId').value             = p.id;
  document.getElementById('modalTitle').textContent        = 'Edit Promotion';
  document.getElementById('modalSubtitle').textContent     = `Editing ${p.code}`;
  document.getElementById('modalSaveBtn').textContent      = 'Save Changes';

  document.getElementById('fCode').value         = p.code || '';
  document.getElementById('fType').value         = p.discount_type || 'percentage';
  document.getElementById('fValue').value        = p.discount_value || '';
  document.getElementById('fMinOrder').value     = p.min_order || '';
  document.getElementById('fStartDate').value    = p.start_date_input || '';
  document.getElementById('fEndDate').value      = p.end_date_input || '';
  document.getElementById('fUsageLimit').value   = p.usage_limit || '';
  document.getElementById('fPerCustomer').value  = p.per_customer_limit || '';
  // Normalize incoming `apply_to` (display text like "All Products" or "Earrings")
  // to match select option values (lowercase keys like 'all', 'earrings').
  (function () {
    var raw = (p.apply_to || '').toString().trim().toLowerCase();
    var map = {
      'all products': 'all',
      'all': 'all',
      'rings': 'rings',
      'necklaces': 'necklaces',
      'earrings': 'earrings',
      'bracelets': 'bracelets'
    };
    var v = map[raw] || 'all';
    document.getElementById('fApplyTo').value = v;
  })();
  document.getElementById('fDescription').value  = p.description || '';

  openModal('promoModal');
}

/* ── SAVE PROMO ── */
async function savePromo() {
  const id = document.getElementById('editPromoId').value;
  const payload = {
    action:             id ? 'update' : 'create',
    id:                 id || null,
    code:               document.getElementById('fCode').value.trim().toUpperCase(),
    discount_type:      document.getElementById('fType').value,
    discount_value:     document.getElementById('fValue').value,
    min_order:          document.getElementById('fMinOrder').value || 0,
    start_date:         document.getElementById('fStartDate').value,
    end_date:           document.getElementById('fEndDate').value || null,
    usage_limit:        document.getElementById('fUsageLimit').value || null,
    per_customer_limit: document.getElementById('fPerCustomer').value || null,
    apply_to:           document.getElementById('fApplyTo').value,
    description:        document.getElementById('fDescription').value.trim(),
  };

  if (!payload.code) { toast('Please enter a promo code', 'error'); return; }
  if (!payload.discount_value && payload.discount_type !== 'free_shipping') {
    toast('Please enter a discount value', 'error'); return;
  }

  const btn = document.getElementById('modalSaveBtn');
  btn.textContent = id ? 'Saving…' : 'Creating…';
  btn.disabled = true;

  try {
    const res = await promoActionRequest(payload);
    if (res && res.promo) upsertPromoInMemory(res.promo);
    closeModal('promoModal');
    renderPromos();
    toast(id ? 'Promotion updated.' : 'Promotion created.', 'ok');
    setTimeout(() => window.location.reload(), 650);
  } catch (err) {
    toast((err && err.message) ? err.message : 'Could not save promotion.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = id ? 'Save Changes' : 'Create Promotion';
  }
}

/* ── CONFIRM DEACTIVATE ── */
function confirmDeactivate(btn) {
  if (!btn) return;
  const id = parseInt(btn.dataset.id, 10);
  const code = btn.dataset.code || '';
  document.getElementById('confirmIcon').textContent  = '⚠️';
  document.getElementById('confirmTitle').textContent = `Deactivate ${code}?`;
  document.getElementById('confirmMsg').textContent   = 'This code will immediately stop working for customers. You can reactivate it anytime.';
  const btnEl = document.getElementById('confirmActionBtn');
  btnEl.textContent = 'Deactivate';
  btnEl.onclick = () => deactivatePromo(id);
  openModal('confirmModal');
}

async function deactivatePromo(id) {
  closeModal('confirmModal');
  try {
    const res = await promoActionRequest({ action: 'deactivate', id });
    if (res && res.promo) upsertPromoInMemory(res.promo);
    renderPromos();
    toast('Promotion deactivated.', 'ok');
    setTimeout(() => window.location.reload(), 650);
  } catch (err) {
    toast((err && err.message) ? err.message : 'Could not deactivate promotion.', 'error');
  }
}

function confirmDelete(btn) {
  if (!btn) return;
  const id = parseInt(btn.dataset.id, 10);
  const code = btn.dataset.code || '';
  document.getElementById('confirmIcon').textContent  = '🗑️';
  document.getElementById('confirmTitle').textContent = `Delete ${code}?`;
  document.getElementById('confirmMsg').textContent   = 'This will permanently remove the promotion from the system.';
  const btnEl = document.getElementById('confirmActionBtn');
  btnEl.textContent = 'Delete';
  btnEl.onclick = () => deletePromo(id);
  openModal('confirmModal');
}

async function deletePromo(id) {
  closeModal('confirmModal');
  try {
    const res = await promoActionRequest({ action: 'delete', id });
    if (res && res.ok) {
      // remove from memory list
      allPromos = allPromos.filter(x => Number(x.id) !== Number(id));
      renderPromos();
      toast('Promotion deleted.', 'ok');
      setTimeout(() => window.location.reload(), 650);
    } else {
      toast('Could not delete promotion.', 'error');
    }
  } catch (err) {
    toast((err && err.message) ? err.message : 'Could not delete promotion.', 'error');
  }
}

/* ── DUPLICATE ── */
function duplicatePromo(id) {
  const p = allPromos.find(x => Number(x.id) === Number(id));
  if (!p) {
    toast('Promotion not found.', 'error');
    return;
  }
  openCreateModal();
  document.getElementById('fCode').value = (p.code || '').toUpperCase() + '_COPY';
  document.getElementById('fType').value = p.discount_type || 'percentage';
  document.getElementById('fValue').value = p.discount_value || '';
  document.getElementById('fMinOrder').value = p.min_order || '';
  document.getElementById('fStartDate').value = p.start_date_input || '';
  document.getElementById('fEndDate').value = p.end_date_input || '';
  document.getElementById('fUsageLimit').value = p.usage_limit || '';
  document.getElementById('fDescription').value = p.description || '';
}

/* ── EXPORT ── */
function exportPromos() {
  window.location.href = 'reports.php';
}

/* ── MODAL HELPERS ── */
function openModal(id)  { document.getElementById(id).classList.add('on'); }
function closeModal(id) { document.getElementById(id).classList.remove('on'); }

function clearForm() {
  ['fCode','fValue','fMinOrder','fStartDate','fEndDate','fUsageLimit','fPerCustomer','fDescription'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('fType').value    = 'percentage';
  document.getElementById('fApplyTo').value = 'all';
}

/* ── LOGOUT ── */
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', () => { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}

/* ── GLOBAL SEARCH ── */
let searchTimeout;
const globalSearchEl = document.getElementById('globalSearch');
if (globalSearchEl) {
  globalSearchEl.addEventListener('input', e => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();
    if (q.length < 2) return;
    searchTimeout = setTimeout(() => {
      window.location.href = `search.php?q=${encodeURIComponent(q)}`;
    }, 500);
  });
}

/* ── AUTO UPPERCASE CODE FIELD ── */
document.getElementById('fCode').addEventListener('input', e => {
  const pos = e.target.selectionStart;
  e.target.value = e.target.value.toUpperCase();
  e.target.setSelectionRange(pos, pos);
});

/* ── CLOSE MODAL ON BACKDROP CLICK ── */
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => {
  if (e.target === m) m.classList.remove('on');
}));

function loadPageData() {
  setLoading(false);
  loadUser();
  loadBadges();
  loadStats();
  loadPromos();
}

document.addEventListener('DOMContentLoaded', loadPageData);