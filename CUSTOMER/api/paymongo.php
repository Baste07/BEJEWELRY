<?php
declare(strict_types=1);

/**
 * PayMongo Checkout API (https://developers.paymongo.com)
 * Amounts are in centavos (1 PHP = 100 centavos).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf_helper.php';

function paymongo_is_configured(): bool
{
    return defined('PAYMONGO_SECRET_KEY') && PAYMONGO_SECRET_KEY !== '';
}

/**
 * PayMongo uses `billing` for the buyer shown on the hosted checkout page.
 * If omitted, the page may show the merchant (PayMongo dashboard) account instead.
 *
 * @param array<string, mixed> $billing Keys: name, email, phone, address (line1, city, state, postal_code, country)
 * @return array{checkout_url:string, checkout_session_id:string, raw:array}
 */
function paymongo_create_checkout_session(
    array $lineItems,
    string $successUrl,
    string $cancelUrl,
    array $metadata = [],
    ?array $billing = null
): array {
    if (!paymongo_is_configured()) {
        throw new RuntimeException('PayMongo is not configured. Set PAYMONGO_SECRET_KEY in api/config.php');
    }

    $body = [
        'data' => [
            'attributes' => [
                'line_items' => $lineItems,
                'payment_method_types' => ['card', 'gcash', 'paymaya'],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'description' => 'Bejewelry order',
            ],
        ],
    ];
    if ($metadata !== []) {
        $body['data']['attributes']['metadata'] = $metadata;
    }
    if ($billing !== null && $billing !== []) {
        $body['data']['attributes']['billing'] = $billing;
    }

    $raw = paymongo_request('POST', '/checkout_sessions', $body);
    $data = $raw['data'] ?? [];
    $attrs = $data['attributes'] ?? [];
    $checkoutUrl = $attrs['checkout_url'] ?? '';
    $id = $data['id'] ?? '';
    if ($checkoutUrl === '' || $id === '') {
        throw new RuntimeException('PayMongo did not return a checkout URL.');
    }

    return [
        'checkout_url' => $checkoutUrl,
        'checkout_session_id' => $id,
        'raw' => $raw,
    ];
}

/** @return array Decoded JSON */
function paymongo_get_checkout_session(string $checkoutSessionId): array
{
    return paymongo_request('GET', '/checkout_sessions/' . rawurlencode($checkoutSessionId), null);
}

/** @return array Decoded JSON */
function paymongo_get_payment_intent(string $paymentIntentId): array
{
    return paymongo_request('GET', '/payment_intents/' . rawurlencode($paymentIntentId), null);
}

/**
 * @param 'GET'|'POST' $method
 */
function paymongo_request(string $method, string $path, ?array $body): array
{
    $key = trim(PAYMONGO_SECRET_KEY);
    $url = 'https://api.paymongo.com/v1' . $path;
    $ch = curl_init($url);
    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($key . ':'),
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
    ];
    if ($method === 'POST') {
        csrf_validate();
        $opts[CURLOPT_POST] = true;
        $enc = json_encode($body ?? []);
        if ($enc === false) {
            throw new RuntimeException('JSON encode failed');
        }
        $opts[CURLOPT_POSTFIELDS] = $enc;
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($resp === false) {
        throw new RuntimeException('PayMongo request failed: ' . $err);
    }
    $decoded = json_decode($resp, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid PayMongo response.');
    }
    if ($code >= 400) {
        $err0 = $decoded['errors'][0] ?? [];
        $codeStr = is_array($err0) ? (string) ($err0['code'] ?? '') : '';
        $detail = is_array($err0) ? (string) ($err0['detail'] ?? '') : '';
        $msg = $codeStr !== '' && $detail !== ''
            ? $codeStr . ': ' . $detail
            : ($detail !== '' ? $detail : ($codeStr !== '' ? $codeStr : json_encode($decoded)));
        throw new RuntimeException('PayMongo error (' . $code . '): ' . $msg);
    }

    return $decoded;
}

/** Total amount in centavos from PayMongo checkout session response. */
function paymongo_session_total_cents(array $sessionResponse): ?int
{
    $attrs = $sessionResponse['data']['attributes'] ?? [];
    if (isset($attrs['amount']) && is_numeric($attrs['amount'])) {
        return (int) $attrs['amount'];
    }
    // Some API versions expose line_item_total or payments
    $lineItems = $attrs['line_items'] ?? [];
    if (is_array($lineItems)) {
        $sum = 0;
        foreach ($lineItems as $li) {
            $a = $li['amount'] ?? null;
            if (is_numeric($a)) {
                $sum += (int) $a;
            }
        }
        if ($sum > 0) {
            return $sum;
        }
    }

    return null;
}

function paymongo_session_is_paid(array $sessionResponse): bool
{
    $attrs = $sessionResponse['data']['attributes'] ?? [];
    $ps = $attrs['payment_status'] ?? '';
    $st = $attrs['status'] ?? '';
    if ($ps === 'paid' || $st === 'paid' || $st === 'complete') {
        return true;
    }
    $payments = $attrs['payments'] ?? [];
    if (is_array($payments)) {
        foreach ($payments as $p) {
            $s = is_array($p) ? ($p['attributes']['status'] ?? '') : '';
            if ($s === 'paid') {
                return true;
            }
        }
    }

    return false;
}
