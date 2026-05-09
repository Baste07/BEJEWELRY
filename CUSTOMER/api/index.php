<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — API Router  (api/index.php)
   
   URL structure (via .htaccess):
     /api/auth/login        → auth.php?action=login
     /api/auth/register     → auth.php?action=register
     /api/products          → products.php
     /api/products/123      → products.php?id=123
     /api/cart              → cart.php
     /api/wishlist          → resources.php?resource=wishlist
     /api/orders            → resources.php?resource=orders
     /api/users/me          → resources.php?resource=users&sub=me
     /api/users/me/addresses→ resources.php?resource=users&sub=addresses
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

// Parse the URI and strip the API base prefix.
// Supports both "/api/..." and "/INFOSEC/CUSTOMER/api/..." URL forms.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/INFOSEC/CUSTOMER/api#', '', $uri);
$uri = preg_replace('#^/[^/]+/CUSTOMER/api#', '', $uri);
$uri = preg_replace('#^/api#', '', $uri);
$uri = rtrim($uri, '/');
$parts  = array_values(array_filter(explode('/', $uri)));
// e.g. ['auth','login'] or ['products','5'] or ['users','me','addresses']

$segment0 = $parts[0] ?? '';
$segment1 = $parts[1] ?? '';
$segment2 = $parts[2] ?? '';

switch ($segment0) {

    // ── Auth ──────────────────────────────────────────────
    case 'auth':
        $_GET['action'] = $segment1; // login | register | logout | me
        require __DIR__ . '/auth.php';
        break;

    // ── Products ──────────────────────────────────────────
    case 'products':
        if ($segment1 === 'upload') {
            $_GET['action'] = 'upload';
        } elseif (is_numeric($segment1)) {
            $_GET['id'] = (int)$segment1;
        }
        require __DIR__ . '/products.php';
        break;

    // ── Cart ──────────────────────────────────────────────
    case 'cart':
        if (is_numeric($segment1)) $_GET['id'] = (int)$segment1;
        require __DIR__ . '/cart.php';
        break;

    // ── Wishlist ──────────────────────────────────────────
    case 'wishlist':
        $_GET['resource']   = 'wishlist';
        if (is_numeric($segment1)) $_GET['product_id'] = (int)$segment1;
        require __DIR__ . '/resources.php';
        break;

    // ── Orders ────────────────────────────────────────────
    case 'orders':
        $_GET['resource'] = 'orders';
        if ($segment1) $_GET['id'] = $segment1;
        require __DIR__ . '/resources.php';
        break;

    // ── Users ─────────────────────────────────────────────
    case 'users':
        $_GET['resource'] = 'users';
        $_GET['sub']      = $segment2 ?: 'me'; // me | addresses
        require __DIR__ . '/resources.php';
        break;

    // ── Support Tickets ───────────────────────────────────
    case 'tickets':
        if (is_numeric($segment1)) $_GET['id'] = (int)$segment1;
        require __DIR__ . '/tickets.php';
        break;

    // ── Admin Dashboard ─────────────────────────────────────
    case 'dashboard':
        // Pass second segment into dashboard.php
        $_GET['__segment1'] = $segment1;
        require __DIR__ . '/dashboard.php';
        break;

    // ── Admin Orders ────────────────────────────────────────
    case 'admin':
        if ($segment1 === 'orders') {
            $_GET['__segment1'] = $segment2 ?: 'list';
            require __DIR__ . '/admin_orders.php';
            break;
        }
        if ($segment1 === 'staff-list') {
            require __DIR__ . '/admin_staff.php';
            break;
        }
        respondError('Not found.', 404);
        break;

    // ── Carrier delivery confirmation ───────────────────────
    case 'carrier':
        if ($segment1 === 'deliveries' && $segment2 === 'confirm') {
            require __DIR__ . '/carrier_delivery_confirm.php';
            break;
        }
        respondError('Not found.', 404);
        break;

    default:
        respond(['name' => 'Bejewelry API', 'version' => '1.0', 'status' => 'ok']);
}