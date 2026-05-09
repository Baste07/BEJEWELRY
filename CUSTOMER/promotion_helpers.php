<?php
declare(strict_types=1);

function bejewelry_normalize_promotion_row(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'code' => strtoupper(trim((string) ($row['code'] ?? ''))),
        'name' => trim((string) ($row['name'] ?? '')),
        'type' => (string) ($row['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent',
        'value' => (float) ($row['value'] ?? 0),
        'min_order' => (float) ($row['min_order'] ?? 0),
        'max_uses' => $row['max_uses'] !== null ? (int) $row['max_uses'] : null,
        'used_count' => (int) ($row['used_count'] ?? 0),
        'start_at' => ($row['start_at'] ?? null) !== null && trim((string) $row['start_at']) !== '' ? (string) $row['start_at'] : null,
        'end_at' => ($row['end_at'] ?? null) !== null && trim((string) $row['end_at']) !== '' ? (string) $row['end_at'] : null,
        'is_active' => (int) ($row['is_active'] ?? 1),
        'apply_to' => strtolower(trim((string) ($row['apply_to'] ?? 'all'))) ?: 'all',
    ];
}

function bejewelry_promotion_is_valid(array $promotion, float $subtotal, ?string $now = null): bool
{
    $nowTs = $now ?? date('Y-m-d H:i:s');
    if ((int) ($promotion['is_active'] ?? 0) !== 1) {
        return false;
    }
    if (!empty($promotion['start_at']) && (string) $promotion['start_at'] > $nowTs) {
        return false;
    }
    if (!empty($promotion['end_at']) && (string) $promotion['end_at'] < $nowTs) {
        return false;
    }
    $maxUses = $promotion['max_uses'] !== null ? (int) $promotion['max_uses'] : null;
    if ($maxUses !== null && (int) ($promotion['used_count'] ?? 0) >= $maxUses) {
        return false;
    }
    if ($subtotal < (float) ($promotion['min_order'] ?? 0)) {
        return false;
    }
    return true;
}

function bejewelry_fetch_active_promotions(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, code, name, type, value, min_order, max_uses, used_count, start_at, end_at, is_active, apply_to FROM promotions WHERE is_active = 1 ORDER BY min_order DESC, value DESC, code ASC');
    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $promo = bejewelry_normalize_promotion_row($row);
        if ($promo['max_uses'] !== null && $promo['used_count'] >= $promo['max_uses']) {
            continue;
        }
        $items[] = $promo;
    }
    return $items;
}

function bejewelry_find_promotion_by_code(PDO $pdo, string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, code, name, type, value, min_order, max_uses, used_count, start_at, end_at, is_active, apply_to FROM promotions WHERE UPPER(code) = ? LIMIT 1');
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return bejewelry_normalize_promotion_row($row);
}

function bejewelry_find_promotion_by_id(PDO $pdo, int $promotionId): ?array
{
    if ($promotionId < 1) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, code, name, type, value, min_order, max_uses, used_count, start_at, end_at, is_active, apply_to FROM promotions WHERE id = ? LIMIT 1');
    $stmt->execute([$promotionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return bejewelry_normalize_promotion_row($row);
}

function bejewelry_calculate_promotion_discount(array $promotion, float $subtotal, float $shippingFee): float
{
    if (!bejewelry_promotion_is_valid($promotion, $subtotal)) {
        return 0.0;
    }

    if (($promotion['type'] ?? 'percent') === 'fixed') {
        return round(min((float) $promotion['value'], max(0.0, $subtotal + $shippingFee)), 2);
    }

    return round(max(0.0, $subtotal * ((float) $promotion['value'] / 100)), 2);
}

function bejewelry_format_money(float $amount): string
{
    return '₱' . number_format(max(0.0, $amount), 2);
}