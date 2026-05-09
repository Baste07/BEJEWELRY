/* ═══════════════════════════════════════════════════════════════════════
   BEJEWELRY — Customer API (auth uses JSON; other methods may be stubbed)
═══════════════════════════════════════════════════════════════════════ */

const Auth = {
  getToken  : () => (typeof window !== 'undefined' && localStorage.getItem('bejewelry_token')) || null,
  setToken  : (t) => {
    if (typeof window === 'undefined') return;
    if (t) localStorage.setItem('bejewelry_token', t);
    else localStorage.removeItem('bejewelry_token');
  },
  clearToken: () => { if (typeof window !== 'undefined') localStorage.removeItem('bejewelry_token'); },
  getUser   : () => (typeof window !== 'undefined' && window.__USER__) || null,
  setUser   : (u) => {
    if (typeof window === 'undefined') return;
    window.__USER__ = u || null;
  },
  clearUser : () => {
    if (typeof window === 'undefined') return;
    window.__USER__ = null;
  },
  isLoggedIn: () => !!(typeof window !== 'undefined' && (window.__USER__ || localStorage.getItem('bejewelry_token'))),
};

function _noApi(name) {
  return () => Promise.reject(new Error('Use page data or form submit; API disabled.'));
}

const API = {
  request: async (method, path, payload = null) => {
    const headers = {};
    const token = Auth.getToken();
    const csrfToken = (typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]'))
      ? document.querySelector('meta[name="csrf-token"]').content
      : '';

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    if (String(method).toUpperCase() === 'POST' && csrfToken) {
      headers['X-CSRF-Token'] = csrfToken;
    }

    const options = { method, headers };

    if (payload !== null && payload !== undefined) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(payload);
    }

    const response = await fetch(path, options);
    const text = await response.text();
    let data = null;

    if (text) {
      try {
        data = JSON.parse(text);
      } catch {
        data = { message: text };
      }
    }

    if (!response.ok) {
      throw new Error(data?.error || data?.message || 'Request failed');
    }

    return data;
  },
  /**
   * Login: email+password, then optionally { totp_token, totp } for Google Authenticator.
   * Returns { step: 'totp', totp_token } when a second step is needed; otherwise { token, user }.
   */
  login: async (email, password, totpOpts = {}) => {
    const body = { email, password };
    const csrfToken = (typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]'))
      ? document.querySelector('meta[name="csrf-token"]').content
      : '';
    if (totpOpts.totp_token && totpOpts.totp) {
      body.totp_token = totpOpts.totp_token;
      body.totp = totpOpts.totp;
    }
    const headers = { 'Content-Type': 'application/json' };
    if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
    const r = await fetch('api/auth/login', {
      method: 'POST',
      headers,
      body: JSON.stringify(body),
    });
    const data = await r.json().catch(() => ({}));
    if (data.requires_totp && data.totp_token) {
      return { step: 'totp', totp_token: data.totp_token, message: data.message };
    }
    if (data.requires_totp_setup) {
      throw new Error(data.message || 'Open the website once to set up Google Authenticator.');
    }
    if (!r.ok) throw new Error(data.error || 'Login failed');
    if (data.token) Auth.setToken(data.token);
    return data;
  },
  register: async (payload) => {
    const csrfToken = (typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]'))
      ? document.querySelector('meta[name="csrf-token"]').content
      : '';
    const headers = { 'Content-Type': 'application/json' };
    if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
    const r = await fetch('api/auth/register', {
      method: 'POST',
      headers,
      body: JSON.stringify(payload),
    });
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.error || 'Registration failed');
    return data;
  },
  logout   : async () => {
    Auth.clearToken();
    Auth.clearUser();
    try {
      await fetch('logout.php', { method: 'GET', credentials: 'same-origin', cache: 'no-store' });
    } catch {
      // Ignore network errors and let caller decide navigation.
    }
    return null;
  },
  getMe    : () => Promise.resolve((typeof window !== 'undefined' && window.__USER__) || null),
  getProducts : _noApi('getProducts'),
  getProduct  : _noApi('getProduct'),
  getCart     : () => Promise.resolve({ items: (typeof window !== 'undefined' && window.__CART__) || [] }),
  addCartItem : _noApi('addCartItem'),
  updateCartItem : _noApi('updateCartItem'),
  removeCartItem : _noApi('removeCartItem'),
  clearCart     : _noApi('clearCart'),
  getWishlist   : _noApi('getWishlist'),
  addToWishlist : _noApi('addToWishlist'),
  removeFromWishlist : _noApi('removeFromWishlist'),
  getOrders  : _noApi('getOrders'),
  getOrder   : _noApi('getOrder'),
  placeOrder : _noApi('placeOrder'),
  getProfile : _noApi('getProfile'),
  updateProfile : _noApi('updateProfile'),
  getAddresses  : _noApi('getAddresses'),
  addAddress    : _noApi('addAddress'),
  updateAddress : _noApi('updateAddress'),
  deleteAddress : _noApi('deleteAddress'),
};
