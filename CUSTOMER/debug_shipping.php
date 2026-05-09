<?php
require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/admin/settings/_settings_db.php';

try {
    $pdo = settingsPdo();
    $shipping = settingsGetJson($pdo, 'shipping', ['shipping_fee' => 150, 'free_ship_threshold' => 2000]);
    echo "DB Shipping Settings:\n";
    echo json_encode($shipping, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
