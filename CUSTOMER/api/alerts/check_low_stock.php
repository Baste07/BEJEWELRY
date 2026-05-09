<?php
/**
 * Low Stock Alert Check
 * Checks if low stock alert notifications are enabled and sends alerts to inventory manager.
 * Can be triggered:
 * - From inventory page load
 * - From product update when stock falls below threshold
 * - From scheduled task/cron job
 */

declare(strict_types=1);

require_once __DIR__ . '/../../admin/db.php';
require_once __DIR__ . '/../../admin/settings/_settings_db.php';
require_once __DIR__ . '/../registration_helpers.php';
require_once __DIR__ . '/../helpers.php';

setHeaders();

try {
    $pdo = adminDb();
    $settingsPdo = settingsPdo();

    // Check if low stock alerts are enabled
    $notificationSettings = settingsGetJson($settingsPdo, 'notifications', []);
    $lowStockEnabled = !empty($notificationSettings['low_stock']);

    if (!$lowStockEnabled) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'Low stock alerts disabled', 'sent' => false]);
        exit;
    }

    // Get admin email for alerts (from settings)
    $adminEmail = trim((string) ($notificationSettings['admin_email'] ?? ''));
    if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        // Fallback: get email from inventory manager user
        $stmt = $pdo->query("SELECT email, first_name FROM users WHERE role = 'inventory' LIMIT 1");
        $inventoryUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inventoryUser || empty($inventoryUser['email'])) {
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'No inventory manager email configured', 'sent' => false]);
            exit;
        }
        $adminEmail = $inventoryUser['email'];
        $inventoryName = trim((string) (($inventoryUser['first_name'] ?? '') . ' ' . ($inventoryUser['last_name'] ?? '')));
    } else {
        // Get inventory manager name if available
        $stmt = $pdo->query("SELECT first_name, last_name FROM users WHERE role = 'inventory' LIMIT 1");
        $inventoryUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $inventoryName = $inventoryUser 
            ? trim((string) (($inventoryUser['first_name'] ?? '') . ' ' . ($inventoryUser['last_name'] ?? '')))
            : 'Inventory Manager';
    }

    // Get low stock products (stock <= 5)
    $lowThreshold = 5;
    $stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE stock > 0 AND stock <= ? ORDER BY stock ASC');
    $stmt->execute([$lowThreshold]);
    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lowStockProducts)) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'No low stock products', 'sent' => false]);
        exit;
    }

    // Send alert email
    $emailSent = send_low_stock_alert($adminEmail, $inventoryName, $lowStockProducts);

    http_response_code(200);
    echo json_encode([
        'ok' => $emailSent,
        'message' => $emailSent ? 'Alert sent successfully' : 'Failed to send alert',
        'sent' => $emailSent,
        'low_stock_count' => count($lowStockProducts),
        'products' => $lowStockProducts,
    ]);

} catch (Throwable $e) {
    error_log('Low stock alert check failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Internal error']);
}
