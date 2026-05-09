// Simple branded confirm modal (replaces window.confirm for key actions like logout).
// Usage: window.adminConfirm(message, onOk, { okText, cancelText })
(function () {
  function ensureModal() {
    let root = document.getElementById('adminConfirmModal');
    if (root) return root;

    root = document.createElement('div');
    root.id = 'adminConfirmModal';
    root.style.cssText = [
      'position:fixed', 'inset:0', 'display:none', 'align-items:center', 'justify-content:center',
      'padding:16px', 'z-index:100000', 'background:rgba(36,20,24,.55)', 'backdrop-filter:blur(6px)'
    ].join(';');

    root.innerHTML = `
      <div style="
        width:min(420px, 94vw);
        background:var(--white, #fff);
        border:1px solid var(--border, #ECDCE0);
        border-radius:16px;
        box-shadow:0 20px 60px rgba(36,20,24,.28);
        overflow:hidden;
      ">
        <div style="
          padding:16px 18px;
          border-bottom:1px solid var(--border, #ECDCE0);
          background:linear-gradient(90deg, var(--blush, #FEF1F3), var(--white, #fff));
          display:flex; align-items:center; gap:10px;
        ">
          <div style="
            width:36px;height:36px;border-radius:12px;
            display:flex;align-items:center;justify-content:center;
            background:linear-gradient(135deg, var(--rose, #D96070), var(--rose-deep, #B03050));
            color:#fff; font-weight:800;
          ">!</div>
          <div style="font-family:var(--fd, Georgia, serif); font-size:1.02rem; font-weight:600; color:var(--dark, #241418)">
            Confirm
          </div>
        </div>
        <div style="padding:18px; color:var(--dark, #241418); font-size:.92rem; line-height:1.45">
          <div id="adminConfirmText"></div>
        </div>
        <div style="
          padding:14px 18px;
          border-top:1px solid var(--border, #ECDCE0);
          display:flex; justify-content:flex-end; gap:10px;
          background:var(--white, #fff);
        ">
          <button type="button" id="adminConfirmCancel" style="
            padding:10px 16px; border-radius:999px;
            border:1.5px solid var(--border-mid, #DEC8D0);
            background:transparent; color:var(--muted, #7A5E68);
            font-weight:700; font-size:.72rem; letter-spacing:.06em; text-transform:uppercase;
            cursor:pointer;
          ">Cancel</button>
          <button type="button" id="adminConfirmOk" style="
            padding:10px 16px; border-radius:999px;
            border:none;
            background:linear-gradient(135deg, var(--rose, #D96070), var(--rose-deep, #B03050));
            color:#fff;
            font-weight:800; font-size:.72rem; letter-spacing:.06em; text-transform:uppercase;
            cursor:pointer;
            box-shadow:0 6px 22px rgba(176,48,80,.28);
          ">OK</button>
        </div>
      </div>
    `;

    document.body.appendChild(root);

    // Click outside closes
    root.addEventListener('click', (e) => {
      if (e.target === root) hide();
    });

    // Esc closes
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') hide();
    });

    function hide() {
      root.style.display = 'none';
      root.dataset.busy = '';
      root.dataset.onok = '';
    }
    root._hide = hide;
    return root;
  }

  window.adminConfirm = function adminConfirm(message, onOk, opts) {
    const root = ensureModal();
    const txt = root.querySelector('#adminConfirmText');
    const btnOk = root.querySelector('#adminConfirmOk');
    const btnCancel = root.querySelector('#adminConfirmCancel');
    txt.textContent = String(message || 'Are you sure?');
    btnOk.textContent = (opts && opts.okText) ? String(opts.okText) : 'OK';
    btnCancel.textContent = (opts && opts.cancelText) ? String(opts.cancelText) : 'Cancel';

    // Prevent stacking handlers
    const okClone = btnOk.cloneNode(true);
    btnOk.parentNode.replaceChild(okClone, btnOk);
    const cancelClone = btnCancel.cloneNode(true);
    btnCancel.parentNode.replaceChild(cancelClone, btnCancel);

    okClone.addEventListener('click', () => {
      root._hide();
      try { if (typeof onOk === 'function') onOk(); } catch (_) {}
    });
    cancelClone.addEventListener('click', () => root._hide());

    root.style.display = 'flex';
  };
})();

