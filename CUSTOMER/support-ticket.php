<?php
require_once __DIR__ . '/inc.php';

if (!current_user_id()) {
    header('Location: login.php?redirect=' . urlencode('support-ticket.php'));
    exit;
}

$user = current_user();
$cart = get_customer_cart();

function support_ticket_type_label(string $type): string
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

function support_ticket_status_label(string $status): string
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

function support_ticket_resolution_label(?string $type, ?string $resolution): string
{
  $type = (string) $type;
  $resolution = (string) $resolution;
  if ($resolution === '') {
    return '—';
  }

  if ($type === 'not_delivered') {
    return $resolution === 'refund' ? 'Refund' : 'Re-deliver';
  }

  if ($resolution === 'refund') {
    return 'Refund';
  }

  return 'Reorder';
}

function support_ticket_enrich_row(array $ticket): array
{
  $ticket['type_label'] = support_ticket_type_label((string) ($ticket['type'] ?? ''));
  $ticket['status_label'] = support_ticket_status_label((string) ($ticket['status'] ?? ''));
  $ticket['resolution_label'] = support_ticket_resolution_label($ticket['type'] ?? null, $ticket['resolution'] ?? null);
  $ticket['photo_url'] = bejewelry_support_ticket_photo_url($ticket['photo_path'] ?? null);
  $ticket['reviewed_by_name'] = trim((string) (($ticket['reviewer_first_name'] ?? '') . ' ' . ($ticket['reviewer_last_name'] ?? '')));
  return $ticket;
}

function support_ticket_rows(int $userId): array
{
    $stmt = db()->prepare('SELECT t.id, t.user_id, t.order_id, t.type, t.category, t.scope, t.photo_path, t.resolution, t.description, t.status, t.admin_note, t.rejection_reason, t.reviewed_by, t.reviewed_at, t.created_at,
          u.first_name, u.last_name, u.email,
          reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name
        FROM support_tickets t
        JOIN users u ON u.id = t.user_id
        LEFT JOIN users reviewer ON reviewer.id = t.reviewed_by
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC');
    $stmt->execute([$userId]);
  $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  return array_map('support_ticket_enrich_row', $tickets);
}

  function support_ticket_for_order(int $userId, string $orderId): ?array
  {
  $stmt = db()->prepare('SELECT id, user_id, order_id, type, category, scope, photo_path, resolution, description, status, admin_note, rejection_reason, reviewed_by, reviewed_at, created_at
                 FROM support_tickets
                 WHERE user_id = ? AND order_id = ?
                 ORDER BY created_at DESC
                 LIMIT 1');
    $stmt->execute([$userId, $orderId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
  return $ticket ? support_ticket_enrich_row($ticket) : null;
  }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_validate();
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $orderId = trim((string) ($payload['order_id'] ?? ''));
    $type = trim((string) ($payload['type'] ?? ''));
    $category = trim((string) ($payload['category'] ?? 'product'));
    $resolution = trim((string) ($payload['resolution'] ?? ''));
    $desc = trim((string) ($payload['description'] ?? ''));
    $validTypes = ['wrong_item', 'damaged', 'not_delivered', 'missing_item', 'other', 'delayed'];
    $validCategories = ['product','shipping','payment','other'];
    $ticketRules = [
      'wrong_item' => ['photo' => true, 'resolution' => ['refund']],
      'damaged' => ['photo' => true, 'resolution' => ['refund']],
      'not_delivered' => ['photo' => false, 'resolution' => ['refund']],
      'missing_item' => ['photo' => true, 'resolution' => ['refund']],
      'other' => ['photo' => false, 'resolution' => []],
      'delayed' => ['photo' => false, 'resolution' => ['refund']],
    ];

    if ($orderId === '') {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'order_id is required.']);
        exit;
    }
    if (!in_array($type, $validTypes, true)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid ticket type.']);
        exit;
    }
    $rules = $ticketRules[$type] ?? null;
    if ($rules === null) {
      http_response_code(400);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'Invalid ticket type.']);
      exit;
    }
    if (strlen($desc) < 10) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Description must be at least 10 characters.']);
        exit;
    }
    if (!in_array($category, $validCategories, true)) {
      http_response_code(400);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'Invalid category.']);
      exit;
    }
    if (!empty($rules['resolution'])) {
      if ($resolution === '') {
        $resolution = 'refund';
      }
      if (!in_array($resolution, $rules['resolution'], true)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Only refund is allowed as the resolution option.']);
        exit;
      }
    } else {
      $resolution = '';
    }

    $orderStmt = db()->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $orderStmt->execute([$orderId, current_user_id()]);
    if (!$orderStmt->fetch()) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Order not found or does not belong to you.']);
        exit;
    }

    $existingTicket = support_ticket_for_order(current_user_id(), $orderId);
    if ($existingTicket) {
      http_response_code(409);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'error' => 'A ticket for this order already exists.',
        'ticket' => $existingTicket,
      ]);
      exit;
    }

    $photoPath = null;
    if (!empty($rules['photo'])) {
      try {
        $photoPath = bejewelry_store_support_ticket_photo($_FILES['photo'] ?? []);
      } catch (Throwable $e) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
      }
      if (!$photoPath) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Please upload a valid image for this report.']);
        exit;
      }
    }

    // Scope: customer-submitted tickets default to manager scope. Super-admin escalation can be done by admins.
    $scope = 'manager';

    $stmt = db()->prepare('INSERT INTO support_tickets (user_id, order_id, type, category, scope, photo_path, resolution, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([current_user_id(), $orderId, $type, $category, $scope, $photoPath, $resolution !== '' ? $resolution : null, $desc, 'open']);
    $newId = (int) db()->lastInsertId();
    if ($newId <= 0) {
      throw new RuntimeException('Ticket was not saved. Please try again.');
    }

    $ticketStmt = db()->prepare('SELECT t.id, t.user_id, t.order_id, t.type, t.category, t.scope, t.description, t.status, t.admin_note, t.created_at,
                      t.photo_path, t.resolution, t.rejection_reason, t.reviewed_by, t.reviewed_at,
                      u.first_name, u.last_name, u.email,
                      reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name
                                 FROM support_tickets t
                                 JOIN users u ON u.id = t.user_id
                   LEFT JOIN users reviewer ON reviewer.id = t.reviewed_by
                                 WHERE t.id = ? AND t.user_id = ?
                                 LIMIT 1');
    $ticketStmt->execute([$newId, current_user_id()]);
    $ticket = $ticketStmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) {
      throw new RuntimeException('Ticket was not saved correctly. Please refresh and try again.');
    }
    $ticket = support_ticket_enrich_row($ticket);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'Ticket submitted successfully.',
        'ticket' => $ticket,
    ]);
    exit;
  } catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'error' => 'Ticket submission failed on the server.',
      'detail' => $e->getMessage(),
    ]);
    exit;
  }
}

$tickets = support_ticket_rows(current_user_id());
$userJson = json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$cartJson = json_encode($cart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$ticketsJson = json_encode($tickets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$csrfMeta = csrf_meta_tag();

$html = file_get_contents(__DIR__ . '/support-ticket.html');
// Prevent accidental reload loops if a refresh tag is reintroduced in the HTML template.
$html = preg_replace('/\s*<meta\s+http-equiv="refresh"[^>]*>\s*/i', "\n", $html);
$inject = <<<HTML
$csrfMeta
<script>
window.__USER__ = {$userJson};
window.__CART__ = {$cartJson};
window.__TICKETS__ = {$ticketsJson};
</script>
<script>
(function() {
  if (typeof API === 'undefined') return;
  const originalRequest = API.request.bind(API);
  API.request = async function(method, path, payload = null) {
    if (path === '/api/tickets') {
      if (method === 'GET') {
        return { data: window.__TICKETS__ || [], total: (window.__TICKETS__ || []).length };
      }
      if (method === 'POST') {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const response = await fetch('support-ticket.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
          },
          body: JSON.stringify(payload || {}),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(data.error || data.message || 'Request failed');
        }
        if (data.ticket) {
          window.__TICKETS__ = [data.ticket].concat(window.__TICKETS__ || []);
        }
        return data;
      }
    }
    return originalRequest(method, path, payload);
  };
})();
</script>
HTML;

$html = str_replace('<script src="js/api.js?v=4"></script>', '<script src="js/api.js?v=4"></script>' . "\n" . $inject, $html);
echo $html;