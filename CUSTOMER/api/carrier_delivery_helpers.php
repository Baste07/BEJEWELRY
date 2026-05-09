<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Confirm a delivery, store proof, and mark the order delivered.
 *
 * @param array<string, mixed> $post
 * @param array<string, mixed> $proof
 * @return array<string, mixed>
 */
function carrier_confirm_delivery(array $post, array $proof): array
{
    $orderId = trim((string) ($post['order_id'] ?? ''));
    $carrierName = trim((string) ($post['carrier_name'] ?? ''));
    $carrierReference = trim((string) ($post['carrier_reference'] ?? ''));
    $note = trim((string) ($post['note'] ?? ''));
    $deliveredAtRaw = trim((string) ($post['delivered_at'] ?? ''));
    $courierUserId = (int) ($post['courier_user_id'] ?? 0);

    if ($orderId === '' || $carrierName === '') {
        throw new RuntimeException('order_id and carrier_name are required.');
    }

    if (!isset($proof['error']) || (int) $proof['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Could not receive proof photo upload.');
    }

    $size = (int) ($proof['size'] ?? 0);
    if ($size <= 0 || $size > CARRIER_PROOF_MAX_BYTES) {
        throw new RuntimeException('Proof photo must be smaller than 8 MB.');
    }

    $tmpPath = (string) ($proof['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid proof upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath) ?: '';
    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($mimeMap[$mime])) {
        throw new RuntimeException('Proof photo must be a JPG, PNG, or WEBP image.');
    }

    $deliveredAt = null;
    if ($deliveredAtRaw !== '') {
        try {
            $deliveredAt = new DateTimeImmutable($deliveredAtRaw);
        } catch (Throwable $e) {
            throw new RuntimeException('Invalid delivered_at value.');
        }
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, status, courier_user_id FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    if ($courierUserId > 0) {
        $assignedCourierId = (int) ($order['courier_user_id'] ?? 0);
        if ($assignedCourierId <= 0 || $assignedCourierId !== $courierUserId) {
            throw new RuntimeException('Order is not assigned to your courier account.');
        }
    }

    $orderStatus = (string) ($order['status'] ?? '');

    if ($orderStatus === 'cancelled') {
        throw new RuntimeException('Cancelled orders cannot be confirmed as delivered.');
    }

    if (!in_array($orderStatus, ['shipped', 'delivered'], true)) {
        throw new RuntimeException('Only shipped orders can be marked as delivered.');
    }

    $proofDir = rtrim(DELIVERY_PROOF_DIR, "\\/") . DIRECTORY_SEPARATOR;
    if (!is_dir($proofDir) && !mkdir($proofDir, 0775, true) && !is_dir($proofDir)) {
        throw new RuntimeException('Could not create proof storage directory.');
    }

    $safeOrderId = preg_replace('/[^A-Za-z0-9_-]+/', '_', $orderId) ?: 'order';
    $fileName = 'delivery_' . $safeOrderId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $mimeMap[$mime];
    $targetPath = $proofDir . $fileName;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('Could not save proof photo.');
    }

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO order_delivery_proofs (order_id, carrier_name, carrier_reference, proof_photo, note, delivered_at)
             VALUES (?,?,?,?,?,?)'
        );
        $ins->execute([
            $orderId,
            $carrierName,
            $carrierReference !== '' ? $carrierReference : null,
            $fileName,
            $note !== '' ? $note : null,
            $deliveredAt?->format('Y-m-d H:i:s'),
        ]);

        $noteParts = [
            'carrier_delivery:' . $carrierName,
            'proof:' . $fileName,
        ];
        if ($carrierReference !== '') {
            $noteParts[] = 'ref:' . $carrierReference;
        }
        if ($deliveredAt !== null) {
            $noteParts[] = 'delivered_at:' . $deliveredAt->format(DATE_ATOM);
        }
        if ($note !== '') {
            $noteParts[] = 'note:' . $note;
        }

        $currentNotesStmt = $pdo->prepare('SELECT notes FROM orders WHERE id = ? LIMIT 1');
        $currentNotesStmt->execute([$orderId]);
        $currentNotes = trim((string) ($currentNotesStmt->fetchColumn() ?: ''));
        $newNotes = $currentNotes !== '' ? $currentNotes . ' | ' . implode(' | ', $noteParts) : implode(' | ', $noteParts);

        $upd = $pdo->prepare('UPDATE orders SET status = ?, notes = ? WHERE id = ?');
        $upd->execute(['delivered', $newNotes, $orderId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
        throw new RuntimeException('Could not confirm delivery.');
    }

    return [
        'ok' => true,
        'order_id' => $orderId,
        'status' => 'delivered',
        'carrier_name' => $carrierName,
        'proof_url' => DELIVERY_PROOF_URL . rawurlencode($fileName),
    ];
}