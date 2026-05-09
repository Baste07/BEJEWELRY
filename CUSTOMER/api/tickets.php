<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Support Tickets Endpoint
   GET    /api/tickets          → list user's tickets
   POST   /api/tickets          → submit new ticket
   PATCH  /api/tickets?id=N     → update status/note (admin)
   GET    /api/tickets?id=N     → single ticket
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf_helper.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$auth   = requireAuth();
$userId = $auth['user_id'];
$role = $auth['role'] ?? 'customer';
$isStaff = in_array($role, ['super_admin','manager','inventory'], true);
$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function ticket_type_label(string $type): string
{
    return [
        'wrong_item' => 'Wrong item received',
        'damaged' => 'Item arrived damaged',
        'not_delivered' => 'Order not delivered',
        'missing_item' => 'Missing item in order',
        'other' => 'Other',
        'delayed' => 'Shipment delayed',
    ][$type] ?? $type;
}

function ticket_status_label(string $status): string
{
    return [
        'open' => 'Pending',
        'resolved' => 'Approved',
        'closed' => 'Rejected',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ][$status] ?? ucfirst($status);
}

function ticket_resolution_label(?string $type, ?string $resolution): string
{
    $type = (string) $type;
    $resolution = (string) $resolution;
    if ($resolution === '') {
        return '—';
    }
    if ($type === 'not_delivered') {
        return $resolution === 'refund' ? 'Refund' : 'Re-deliver';
    }
    return $resolution === 'refund' ? 'Refund' : 'Reorder';
}

function ticket_enrich_row(array $row): array
{
    $row['type_label'] = ticket_type_label((string) ($row['type'] ?? ''));
    $row['status_label'] = ticket_status_label((string) ($row['status'] ?? ''));
    $row['resolution_label'] = ticket_resolution_label($row['type'] ?? null, $row['resolution'] ?? null);
    $row['photo_url'] = bejewelry_support_ticket_photo_url($row['photo_path'] ?? null);
    return $row;
}

// ── GET /api/tickets?id=N  →  single ticket ───────────────
if ($method === 'GET' && $ticketId) {
    if ($isStaff) {
        if ($role === 'super_admin') {
            $stmt = db()->prepare("SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ? AND t.order_id LIKE 'ACCDEL-%' LIMIT 1");
            $stmt->execute([$ticketId]);
        } else {
            // manager and inventory can only fetch manager-scoped tickets
            $stmt = db()->prepare('SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ? AND t.scope = ? LIMIT 1');
            $stmt->execute([$ticketId, 'manager']);
        }
    } else {
        $stmt = db()->prepare('SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ? AND t.user_id = ? LIMIT 1');
        $stmt->execute([$ticketId, $userId]);
    }
    $ticket = $stmt->fetch();
    if (!$ticket) respondError('Ticket not found.', 404);
    respond(ticket_enrich_row($ticket));
}

// ── GET /api/tickets  →  list tickets ─────────────────────
if ($method === 'GET') {
    if ($isStaff) {
        // Staff see tickets according to their scope
        if ($role === 'super_admin') {
            // Super admins only see account-deletion requests
            $stmt = db()->prepare("SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.order_id LIKE 'ACCDEL-%' ORDER BY t.created_at DESC");
            $stmt->execute();
        } else {
            // manager and inventory see manager-scoped tickets
            $stmt = db()->prepare("SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.scope = 'manager' ORDER BY t.created_at DESC");
            $stmt->execute();
        }
    } else {
        // Customers see only their own
        $stmt = db()->prepare('
            SELECT * FROM support_tickets
            WHERE user_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userId]);
    }
    $tickets = $stmt->fetchAll();
    $tickets = array_map('ticket_enrich_row', $tickets);
    respond(['data' => $tickets, 'total' => count($tickets)]);
}

// ── POST /api/tickets  →  submit new ticket ───────────────
if ($method === 'POST') {
    csrf_validate();
    $b       = body();
    if (!$b && !empty($_POST)) {
        $b = $_POST;
    }
    $orderId = trim($b['order_id']    ?? '');
    $type    = trim($b['type']        ?? '');
    $category = trim($b['category'] ?? 'product');
    $resolution = trim($b['resolution'] ?? '');
    $desc    = trim($b['description'] ?? '');

    $validTypes = ['wrong_item','damaged','not_delivered','missing_item','other','delayed'];
    $validCategories = ['product','shipping','payment','other'];
    $rules = [
        'wrong_item' => ['photo' => true, 'resolution' => ['refund']],
        'damaged' => ['photo' => true, 'resolution' => ['refund']],
        'not_delivered' => ['photo' => false, 'resolution' => ['refund']],
        'missing_item' => ['photo' => true, 'resolution' => ['refund']],
        'other' => ['photo' => false, 'resolution' => []],
        'delayed' => ['photo' => false, 'resolution' => ['refund']],
    ];

    if (!$orderId)                        respondError('order_id is required.');
    if (!in_array($type, $validTypes))    respondError('Invalid ticket type.');
    if (!in_array($category, $validCategories)) respondError('Invalid ticket category.');
    if (strlen($desc) < 10)              respondError('Description must be at least 10 characters.');

    $rule = $rules[$type] ?? null;
    if ($rule === null) respondError('Invalid ticket type.');
    if (!empty($rule['resolution'])) {
        if ($resolution === '') {
            $resolution = 'refund';
        }
        if (!in_array($resolution, $rule['resolution'], true)) {
            respondError('Only refund is allowed as the resolution option.');
        }
    } else {
        $resolution = '';
    }

    // Verify the order belongs to this user (skip check for admins)
    if (!$isStaff) {
        $oStmt = db()->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $oStmt->execute([$orderId, $userId]);
        if (!$oStmt->fetch()) respondError('Order not found or does not belong to you.', 404);
    }

    $existingStmt = db()->prepare('SELECT id FROM support_tickets WHERE user_id = ? AND order_id = ? LIMIT 1');
    $existingStmt->execute([$userId, $orderId]);
    if ($existingStmt->fetch()) {
        respondError('A ticket for this order already exists.', 409);
    }

    $photoPath = null;
    if (!empty($rule['photo'])) {
        $photoPath = bejewelry_store_support_ticket_photo($_FILES['photo'] ?? []);
        if (!$photoPath) {
            respondError('Please upload a valid image for this report.');
        }
    }

    // Default scope for customer-submitted tickets is 'manager'. Admins can set scope when creating/updating tickets via API.
    $scope = 'manager';
    $stmt = db()->prepare('
        INSERT INTO support_tickets (user_id, order_id, type, category, scope, photo_path, resolution, description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $orderId, $type, $category, $scope, $photoPath, $resolution !== '' ? $resolution : null, $desc, 'open']);
    $newId = (int)db()->lastInsertId();

    respond([
        'id'      => $newId,
        'message' => 'Ticket submitted successfully.'
    ], 201);
}

// ── PATCH /api/tickets?id=N  →  admin update ─────────────
if ($method === 'PATCH' && $ticketId) {
    if (!$isStaff) respondError('Forbidden.', 403);

    $b          = body();
    $status     = $b['status']     ?? null;
    $adminNote  = $b['admin_note'] ?? null;
    $rejectionReason = $b['rejection_reason'] ?? null;
    $reviewedBy = (int) ($b['reviewed_by'] ?? $userId);

    $validStatuses = ['open', 'resolved', 'closed'];
    if ($status && !in_array($status, $validStatuses)) {
        respondError('Invalid status. Must be: open, resolved, or closed.');
    }

    // Ensure the staff user is allowed to update this ticket
    $checkStmt = db()->prepare('SELECT order_id, scope FROM support_tickets WHERE id = ? LIMIT 1');
    $checkStmt->execute([$ticketId]);
    $check = $checkStmt->fetch();
    if (!$check) respondError('Ticket not found.', 404);
    if ($role === 'super_admin') {
        if (!str_starts_with((string)$check['order_id'], 'ACCDEL-')) {
            respondError('Forbidden.', 403);
        }
    } else {
        if (($check['scope'] ?? '') !== 'manager') {
            respondError('Forbidden.', 403);
        }
    }

    $sets   = [];
    $params = [];

    if ($status)              { $sets[] = 'status = ?';     $params[] = $status; }
    if ($adminNote !== null)  { $sets[] = 'admin_note = ?'; $params[] = $adminNote; }
    if ($rejectionReason !== null) { $sets[] = 'rejection_reason = ?'; $params[] = $rejectionReason; }
    if ($status !== null) { $sets[] = 'reviewed_by = ?'; $params[] = $reviewedBy; $sets[] = 'reviewed_at = NOW()'; }

    if (!$sets) respondError('Nothing to update.');

    $params[] = $ticketId;
    db()->prepare('UPDATE support_tickets SET ' . implode(', ', $sets) . ' WHERE id = ?')
        ->execute($params);

    $stmt = db()->prepare('SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?');
    $stmt->execute([$ticketId]);
    respond(ticket_enrich_row($stmt->fetch()));
}

respondError('Method not allowed.', 405);