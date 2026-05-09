// Order Manager current-user loader with manager label normalization.
(function () {
  function initials(name) {
    var s = String(name || '').trim();
    if (!s) return 'OM';
    var parts = s.split(/\s+/).filter(Boolean);
    var a = (parts[0] || '').slice(0, 1);
    var b = (parts.length > 1 ? parts[parts.length - 1] : '').slice(0, 1);
    return (a + b).toUpperCase() || 'OM';
  }

  function normalizeRole(role) {
    var r = String(role || '').trim().toLowerCase();
    if (r === 'manager') return 'Order Manager';
    if (r === 'super_admin') return 'Super Admin';
    if (r === 'inventory') return 'Inventory Manager';
    if (!r) return 'Order Manager';
    return role;
  }

  async function loadWhoAmI() {
    var nameEl = document.getElementById('sbUsername');
    var roleEl = document.getElementById('sbUserRole');
    var avEl = document.getElementById('sbAvatar');
    if (!nameEl && !roleEl && !avEl) return;

    try {
      var url = new URL('../api/auth/session.php', window.location.href);
      var response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
      if (!response.ok) throw new Error(String(response.status));
      var user = await response.json();

      var name = user && user.name ? String(user.name) : 'Order Manager';
      var role = normalizeRole(user && user.role ? String(user.role) : 'manager');

      if (nameEl) nameEl.textContent = name;
      if (roleEl) roleEl.textContent = role;
      if (avEl) avEl.textContent = initials(name);
    } catch (_) {
      if (roleEl && (!roleEl.textContent || roleEl.textContent.trim().toLowerCase() === 'manager')) {
        roleEl.textContent = 'Order Manager';
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadWhoAmI);
  } else {
    loadWhoAmI();
  }
})();
