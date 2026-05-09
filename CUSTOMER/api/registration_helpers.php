<?php
declare(strict_types=1);

/**
 * Shared: password rules, reCAPTCHA v2, activation email (PHPMailer).
 * Used by register.php and api/auth.php.
 */

require_once __DIR__ . '/config.php';

/** Returns null if valid, or error message string. */
function validate_password_complexity(string $pass): ?string
{
    if (strlen($pass) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $pass)) {
        return 'Password must include at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $pass)) {
        return 'Password must include at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $pass)) {
        return 'Password must include at least one number.';
    }
    // Require a punctuation-style symbol (not just underscore).
    if (!preg_match('/[^A-Za-z0-9_]/', $pass)) {
        return 'Password must include at least one symbol (e.g. !@#$%).';
    }
    return null;
}

function bejewelry_verify_local_captcha(string $input): bool
{
    $expected = isset($_SESSION['captcha_text']) ? (string) $_SESSION['captcha_text'] : '';
    $ts = isset($_SESSION['captcha_ts']) ? (int) $_SESSION['captcha_ts'] : 0;
    if ($expected === '' || $ts <= 0) {
        return false;
    }
    if (time() - $ts > 10 * 60) {
        return false;
    }

    $value = strtoupper(trim($input));
    return $value !== '' && hash_equals(strtoupper($expected), $value);
}

/** Base URL for links in emails (folder where login.php / activate.php live). */
function customer_public_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/register.php';
    $dir = dirname(str_replace('\\', '/', $script));
    // API lives in /api/; activation page is one level up
    if (str_ends_with($dir, '/api')) {
        $dir = dirname($dir);
    }
    return rtrim($scheme . '://' . $host . $dir, '/');
}

/** Verify Google reCAPTCHA v2 response token. */
function verify_recaptcha_v2(string $response): bool
{
    $response = trim($response);
    if ($response === '') {
        return false;
    }

    // Reject Google public test keys in strict mode so protection is truly enforced.
    if (defined('RECAPTCHA_STRICT_REAL') && RECAPTCHA_STRICT_REAL) {
        if (
            RECAPTCHA_SITE_KEY === '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' ||
            RECAPTCHA_SECRET_KEY === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'
        ) {
            return false;
        }
    }

    $secret = RECAPTCHA_SECRET_KEY;
    if ($secret === '') {
        return false;
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret' => $secret,
                'response' => $response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            'timeout' => 10,
        ],
    ]);
    $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    if ($raw === false) {
        return false;
    }
    $data = json_decode($raw, true);
    return is_array($data) && !empty($data['success']);
}

function send_activation_email(string $toEmail, string $firstName, string $activateUrl): bool
{
    if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
        error_log('Composer autoload missing; cannot send activation email.');
        return false;
    }
    require_once __DIR__ . '/../vendor/autoload.php';

    $user = SMTP_USER;
    $pass = SMTP_PASS;
    if ($user === '' || $pass === '') {
        error_log('SMTP not configured: set SMTP_USER and SMTP_PASS in api/config.php');
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $firstName);
        $mail->Subject = 'Activate your Bejewelry account';
        $safeName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $mail->isHTML(true);
        $mail->Body = <<<HTML
<p>Hello {$safeName},</p>
<p>Thank you for registering with Bejewelry.</p>
<p>Please confirm your email address by clicking the link below:</p>
<p><a href="{$activateUrl}">Activate my account</a></p>
<p>If you did not create an account, you can ignore this email.</p>
HTML;
        $mail->AltBody = "Hello {$firstName},\n\nOpen this link to activate your account:\n{$activateUrl}\n";
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Activation email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send low stock alert email to inventory manager.
 * @param string $inventoryManagerEmail Email address of inventory manager
 * @param string $inventoryManagerName Name of inventory manager (optional)
 * @param array $lowStockProducts Array of products with low stock: [['name'=>'...', 'stock'=>5, 'id'=>1], ...]
 * @return bool True if email sent successfully
 */
function send_low_stock_alert(string $inventoryManagerEmail, string $inventoryManagerName, array $lowStockProducts): bool
{
    if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
        error_log('Composer autoload missing; cannot send low stock alert.');
        return false;
    }
    require_once __DIR__ . '/../vendor/autoload.php';

    $user = SMTP_USER;
    $pass = SMTP_PASS;
    if ($user === '' || $pass === '') {
        error_log('SMTP not configured: set SMTP_USER and SMTP_PASS in api/config.php');
        return false;
    }

    if (empty($lowStockProducts)) {
        return false; // No products to alert about
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($inventoryManagerEmail, $inventoryManagerName ?: 'Inventory Manager');
        $mail->Subject = '⚠️ Low Stock Alert — Bejewelry Inventory';
        
        $safeName = htmlspecialchars($inventoryManagerName ?: 'Inventory Manager', ENT_QUOTES, 'UTF-8');
        $productRows = '';
        foreach ($lowStockProducts as $product) {
            $pName = htmlspecialchars($product['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
            $pStock = (int) ($product['stock'] ?? 0);
            $pId = (int) ($product['id'] ?? 0);
            $productRows .= "<tr><td>{$pName}</td><td>{$pStock}</td><td><a href=\"/BEJEWELRY/CUSTOMER/admin/inventory.php?filter=low\">View</a></td></tr>";
        }

        $mail->isHTML(true);
        $mail->Body = <<<HTML
<p>Hello {$safeName},</p>
<p>The following products have fallen below the low stock threshold (5 units):</p>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:16px 0;">
<thead>
<tr style="background-color:#f0f0f0;">
<th>Product Name</th>
<th>Current Stock</th>
<th>Action</th>
</tr>
</thead>
<tbody>
{$productRows}
</tbody>
</table>
<p>Please review your inventory and reorder as needed.</p>
<p>—<br/>Bejewelry Inventory Management System</p>
HTML;
        
        $altBodyText = "Hello {$inventoryManagerName},\n\nThe following products have low stock:\n\n";
        foreach ($lowStockProducts as $product) {
            $altBodyText .= "- {$product['name']}: {$product['stock']} units\n";
        }
        $altBodyText .= "\nPlease review your inventory and reorder as needed.\n";
        
        $mail->AltBody = $altBodyText;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Low stock alert failed: ' . $e->getMessage());
        return false;
    }
}
