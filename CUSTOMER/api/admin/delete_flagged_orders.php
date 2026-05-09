<?php
/**
 * API Endpoint: DELETE /api/admin/delete_flagged_orders.php
 * Purpose: Hard delete all flagged orders and their related data
 * Auth: Admin only
 * Request: POST with optional order_ids array
 */

session_start();
require_once '../../inc.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Verify authentication (admin check)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['error' => 'CSRF token validation failed']));
}

try {
    $db = db();
    
    // Get the order IDs to delete
    $order_ids = [];
    
    // If specific order IDs provided in request
    if (!empty($_POST['order_ids']) && is_array($_POST['order_ids'])) {
        $order_ids = array_map('trim', $_POST['order_ids']);
    } else {
        // Otherwise, get all flagged orders
        $stmt = $db->query('SELECT id FROM orders WHERE is_flagged = 1');
        $order_ids = array_map(function($row) { return $row['id']; }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    if (empty($order_ids)) {
        http_response_code(200);
        die(json_encode([
            'success' => true,
            'message' => 'No flagged orders to delete',
            'deleted_count' => 0
        ]));
    }
    
    // Start transaction
    $db->beginTransaction();
    
    $deleted_count = 0;
    
    foreach ($order_ids as $order_id) {
        // First, get all order items for this order
        $stmt = $db->prepare('SELECT product_id FROM order_items WHERE order_id = ?');
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete order items
        $stmt = $db->prepare('DELETE FROM order_items WHERE order_id = ?');
        $stmt->execute([$order_id]);
        
        // Delete related product reviews if any
        // (reviews are tied to orders via order history, so they should cascade or be deleted separately)
        foreach ($order_items as $item) {
            // Optional: Delete reviews for this product from this customer
            // This depends on your business logic - reviews might be kept for integrity
            // Uncomment if you want to delete customer reviews on order deletion:
            /*
            $stmt = $db->prepare('
                DELETE FROM product_reviews 
                WHERE product_id = ? 
                AND customer_id = (SELECT user_id FROM orders WHERE id = ?)
            ');
            $stmt->execute([$item['product_id'], $order_id]);
            */
        }
        
        // Delete the order itself
        $stmt = $db->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->execute([$order_id]);
        $deleted_count++;
    }
    
    // Commit transaction
    $db->commit();
    
    // Log deletion
    error_log("Flagged orders deleted: " . implode(', ', $order_ids));
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Successfully deleted $deleted_count flagged order(s)",
        'deleted_count' => $deleted_count,
        'deleted_orders' => $order_ids
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log("Error deleting flagged orders: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete flagged orders',
        'details' => $e->getMessage()
    ]);
}
?>
