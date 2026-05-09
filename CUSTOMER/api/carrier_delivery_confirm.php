<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/carrier_delivery_helpers.php';

setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Method not allowed', 405);
}

csrf_validate();

$carrierKey = trim((string) ($_SERVER['HTTP_X_CARRIER_KEY'] ?? ''));
if ($carrierKey === '' || !hash_equals(trim(CARRIER_WEBHOOK_KEY), $carrierKey)) {
    respondError('Unauthorized', 401);
}

try {
    $result = carrier_confirm_delivery($_POST, $_FILES['proof_photo'] ?? []);
    respond($result, 201);
} catch (Throwable $e) {
    respondError($e->getMessage() ?: 'Could not confirm delivery.', 400);
}