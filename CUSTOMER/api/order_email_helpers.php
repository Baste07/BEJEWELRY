<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

function bejewelry_ensure_order_email_log_table(PDO $pdo): void
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS order_email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                order_id VARCHAR(20) NOT NULL,
                email_type VARCHAR(50) NOT NULL,
                recipient_email VARCHAR(191) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                status ENUM('sent','failed') NOT NULL DEFAULT 'sent',
                error_message TEXT DEFAULT NULL,
                sent_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_order_email_log (user_id, order_id, email_type),
                KEY idx_order_email_user (user_id, created_at),
                KEY idx_order_email_order (order_id, created_at),
                CONSTRAINT fk_order_email_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_order_email_logs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Could not ensure order_email_logs table: ' . $e->getMessage());
    }
}

function bejewelry_order_email_sent(PDO $pdo, int $userId, string $orderId, string $emailType): bool
{
    try {
        bejewelry_ensure_order_email_log_table($pdo);
        $stmt = $pdo->prepare('SELECT status FROM order_email_logs WHERE user_id = ? AND order_id = ? AND email_type = ? LIMIT 1');
        $stmt->execute([$userId, $orderId, $emailType]);
        return (string) ($stmt->fetchColumn() ?: '') === 'sent';
    } catch (Throwable $e) {
        return false;
    }
}

function bejewelry_record_order_email_log(PDO $pdo, int $userId, string $orderId, string $emailType, string $recipientEmail, string $subject, string $status, ?string $errorMessage = null): void
{
    try {
        bejewelry_ensure_order_email_log_table($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO order_email_logs (user_id, order_id, email_type, recipient_email, subject, status, error_message, sent_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE recipient_email = VALUES(recipient_email), subject = VALUES(subject), status = VALUES(status), error_message = VALUES(error_message), sent_at = VALUES(sent_at)'
        );
        $stmt->execute([
            $userId,
            $orderId,
            $emailType,
            $recipientEmail,
            $subject,
            $status,
            $errorMessage,
            $status === 'sent' ? date('Y-m-d H:i:s') : null,
        ]);
    } catch (Throwable $e) {
        error_log('Could not store order email log: ' . $e->getMessage());
    }
}

function bejewelry_order_email_mailer(): ?\PHPMailer\PHPMailer\PHPMailer
{
    if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
        error_log('Composer autoload missing; cannot send order email.');
        return null;
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    if (SMTP_USER === '' || SMTP_PASS === '') {
        error_log('SMTP not configured: set SMTP_USER and SMTP_PASS in api/config.php');
        return null;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->Port = SMTP_PORT;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

    return $mail;
}

function bejewelry_send_order_email(PDO $pdo, int $userId, string $orderId, string $emailType, string $subject, string $htmlBody, string $altBody): bool
{
    if (bejewelry_order_email_sent($pdo, $userId, $orderId, $emailType)) {
        return true;
    }

    $stmt = $pdo->prepare('SELECT o.id, o.status, o.payment_method, o.notes, u.email, u.first_name, u.last_name FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.id = ? AND o.user_id = ? LIMIT 1');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || trim((string) ($order['email'] ?? '')) === '') {
        return false;
    }

    $mail = bejewelry_order_email_mailer();
    if (!$mail) {
        return false;
    }

    $recipientEmail = trim((string) $order['email']);
    $recipientName = trim((string) (($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')));

    try {
        $mail->addAddress($recipientEmail, $recipientName !== '' ? $recipientName : 'Customer');
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody;
        $mail->send();
        bejewelry_record_order_email_log($pdo, $userId, $orderId, $emailType, $recipientEmail, $subject, 'sent');
        return true;
    } catch (Throwable $e) {
        bejewelry_record_order_email_log($pdo, $userId, $orderId, $emailType, $recipientEmail, $subject, 'failed', $e->getMessage());
        error_log('Order email failed (' . $emailType . '): ' . $e->getMessage());
        return false;
    }
}

function bejewelry_send_order_processing_email(PDO $pdo, string $orderId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT o.*, u.email, u.first_name, u.last_name FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.id = ? AND o.user_id = ? LIMIT 1');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return false;
    }

    if (($order['payment_method'] ?? '') !== 'paymongo' && !str_contains((string) ($order['notes'] ?? ''), 'paymongo_session:')) {
        return false;
    }

    $itemsStmt = $pdo->prepare('SELECT name, cat, size, price, qty FROM order_items WHERE order_id = ? ORDER BY id ASC');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $name = trim((string) (($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')));
    $safeName = htmlspecialchars($name !== '' ? $name : 'Customer', ENT_QUOTES, 'UTF-8');
    $orderNumber = htmlspecialchars((string) $order['id'], ENT_QUOTES, 'UTF-8');

    $rows = '';
    foreach ($items as $item) {
        $itemName = htmlspecialchars((string) ($item['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
        $itemCat = htmlspecialchars((string) ($item['cat'] ?? ''), ENT_QUOTES, 'UTF-8');
        $itemSize = htmlspecialchars((string) ($item['size'] ?? ''), ENT_QUOTES, 'UTF-8');
        $qty = (int) ($item['qty'] ?? 1);
        $price = number_format((float) ($item['price'] ?? 0), 2);
        $rows .= '<tr><td style="padding:10px;border-bottom:1px solid #eadde1;">' . $itemName . '<div style="font-size:12px;color:#7a5e68;">' . $itemCat . ($itemSize !== '' ? ' · ' . $itemSize : '') . '</div></td><td style="padding:10px;border-bottom:1px solid #eadde1;text-align:center;">' . $qty . '</td><td style="padding:10px;border-bottom:1px solid #eadde1;text-align:right;">₱' . $price . '</td></tr>';
    }

    $subtotal = number_format((float) ($order['subtotal'] ?? 0), 2);
    $shippingFee = number_format((float) ($order['shipping_fee'] ?? 0), 2);
    $total = number_format((float) ($order['total'] ?? 0), 2);

    $html = <<<HTML
<div style="font-family:Arial,sans-serif;color:#241418;line-height:1.6;">
  <p>Hello {$safeName},</p>
  <p>Your payment was completed and your order is now processing.</p>
  <p><strong>Order #{$orderNumber}</strong></p>
  <table style="width:100%;border-collapse:collapse;margin:16px 0;border:1px solid #eadde1;">
    <thead><tr style="background:#fef1f3;"><th style="padding:10px;">Item</th><th style="padding:10px;text-align:center;">Qty</th><th style="padding:10px;text-align:right;">Price</th></tr></thead>
    <tbody>{$rows}</tbody>
  </table>
  <p><strong>Subtotal:</strong> ₱{$subtotal}<br><strong>Shipping:</strong> ₱{$shippingFee}<br><strong>Total:</strong> ₱{$total}</p>
  <p>We will update your order again once it is shipped.</p>
</div>
HTML;

    $alt = "Hello " . ($name !== '' ? $name : 'Customer') . ", your payment was completed and your order is now processing.\n\nOrder #{$orderId}\nSubtotal: ₱{$subtotal}\nShipping: ₱{$shippingFee}\nTotal: ₱{$total}\n\nWe will update your order again once it is shipped.\n";

    return bejewelry_send_order_email($pdo, $userId, $orderId, 'processing', 'Your Bejewelry order is now processing', $html, $alt);
}

function bejewelry_send_order_completed_email(PDO $pdo, string $orderId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT o.*, u.email, u.first_name, u.last_name FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.id = ? AND o.user_id = ? LIMIT 1');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || ($order['status'] ?? '') !== 'delivered') {
        return false;
    }

    $receiptStmt = $pdo->prepare('SELECT received_at FROM order_receipts WHERE order_id = ? AND user_id = ? LIMIT 1');
    $receiptStmt->execute([$orderId, $userId]);
    if (!$receiptStmt->fetchColumn()) {
        return false;
    }

    $itemCountStmt = $pdo->prepare('SELECT COUNT(DISTINCT product_id) FROM order_items WHERE order_id = ?');
    $itemCountStmt->execute([$orderId]);
    $requiredReviews = (int) ($itemCountStmt->fetchColumn() ?: 0);

    $reviewCountStmt = $pdo->prepare('SELECT COUNT(DISTINCT product_id) FROM product_reviews WHERE order_id = ? AND user_id = ?');
    $reviewCountStmt->execute([$orderId, $userId]);
    $completedReviews = (int) ($reviewCountStmt->fetchColumn() ?: 0);

    $courierStmt = $pdo->prepare('SELECT rating, body, courier_name FROM order_courier_ratings WHERE order_id = ? AND user_id = ? LIMIT 1');
    $courierStmt->execute([$orderId, $userId]);
    $courier = $courierStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (($requiredReviews > 0 && $completedReviews < $requiredReviews) || !$courier) {
        return false;
    }

    $itemsStmt = $pdo->prepare('SELECT oi.name, oi.cat, oi.size, pr.rating AS review_rating, pr.body AS review_body FROM order_items oi LEFT JOIN product_reviews pr ON pr.order_id = oi.order_id AND pr.product_id = oi.product_id AND pr.user_id = ? WHERE oi.order_id = ? ORDER BY oi.id ASC');
    $itemsStmt->execute([$userId, $orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $name = trim((string) (($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')));
    $safeName = htmlspecialchars($name !== '' ? $name : 'Customer', ENT_QUOTES, 'UTF-8');
    $orderNumber = htmlspecialchars((string) $order['id'], ENT_QUOTES, 'UTF-8');
    $courierName = htmlspecialchars((string) ($courier['courier_name'] ?? ($order['courier_name'] ?? 'Delivery service')), ENT_QUOTES, 'UTF-8');
    $courierRating = (int) ($courier['rating'] ?? 0);
    $courierComment = htmlspecialchars(trim((string) ($courier['body'] ?? '')) ?: 'No comment', ENT_QUOTES, 'UTF-8');

    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:10px;border-bottom:1px solid #eadde1;">'
            . htmlspecialchars((string) ($item['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8')
            . '<div style="font-size:12px;color:#7a5e68;">'
            . htmlspecialchars((string) ($item['cat'] ?? ''), ENT_QUOTES, 'UTF-8')
            . ((string) ($item['size'] ?? '') !== '' ? ' · ' . htmlspecialchars((string) $item['size'], ENT_QUOTES, 'UTF-8') : '')
            . '</div></td><td style="padding:10px;border-bottom:1px solid #eadde1;text-align:center;">'
            . ((int) ($item['review_rating'] ?? 0)) . '/5</td><td style="padding:10px;border-bottom:1px solid #eadde1;">'
            . htmlspecialchars(trim((string) ($item['review_body'] ?? '')) ?: 'No comment', ENT_QUOTES, 'UTF-8')
            . '</td></tr>';
    }

    $html = <<<HTML
<div style="font-family:Arial,sans-serif;color:#241418;line-height:1.6;">
  <p>Hello {$safeName},</p>
  <p>Your order has been completed. Here is the summary of the ratings you submitted.</p>
  <p><strong>Order #{$orderNumber}</strong></p>
  <table style="width:100%;border-collapse:collapse;margin:16px 0;border:1px solid #eadde1;">
    <thead><tr style="background:#fef1f3;"><th style="padding:10px;">Product</th><th style="padding:10px;text-align:center;">Rating</th><th style="padding:10px;">Comment</th></tr></thead>
    <tbody>{$rows}</tbody>
  </table>
  <div style="margin-top:20px;padding:16px;border:1px solid #eadde1;border-radius:12px;background:#fff9fb;">
    <h4 style="margin:0 0 8px 0;">Courier feedback</h4>
    <p style="margin:0 0 6px 0;"><strong>{$courierName}</strong></p>
    <p style="margin:0 0 6px 0;">Rating: {$courierRating}/5</p>
    <p style="margin:0;">{$courierComment}</p>
  </div>
  <p style="margin-top:16px;">Thank you for shopping with Bejewelry.</p>
</div>
HTML;

    $alt = "Hello " . ($name !== '' ? $name : 'Customer') . ", your order has been completed.\n\nOrder #{$orderId}\nCourier: " . (string) ($courier['courier_name'] ?? ($order['courier_name'] ?? 'Delivery service')) . "\nCourier rating: {$courierRating}/5\n\nThank you for shopping with Bejewelry.\n";

    return bejewelry_send_order_email($pdo, $userId, $orderId, 'completed', 'Your Bejewelry order is complete', $html, $alt);
}
