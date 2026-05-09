<?php
declare(strict_types=1);

/**
 * Minimal TOTP (RFC 6238) — Google Authenticator compatible.
 */
final class TotpHelper
{
    private const TIME_STEP = 30;
    private const DIGITS = 6;

    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $randomBytes = random_bytes(16);
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[ord($randomBytes[$i]) % 32];
        }
        return $secret;
    }

    public static function verify(string $secret, string $code): bool
    {
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }
        $counter = (int) floor(time() / self::TIME_STEP);
        for ($i = -1; $i <= 1; $i++) {
            $checkCounter = $counter + $i;
            $secretBin = self::base32Decode($secret);
            $time = pack('N*', 0) . pack('N*', $checkCounter);
            $hash = hash_hmac('sha1', $time, $secretBin, true);
            $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
            $expected = (
                ((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % (10 ** self::DIGITS);
            if ($code === str_pad((string) $expected, self::DIGITS, '0', STR_PAD_LEFT)) {
                return true;
            }
        }
        return false;
    }

    public static function getProvisioningUrl(string $secret, string $username, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $username);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
        ]);
        return 'otpauth://totp/' . $label . '?' . $params;
    }

    private static function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bits = 0;
        for ($i = 0; $i < strlen($input); $i++) {
            $pos = strpos($alphabet, $input[$i]);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xff);
            }
        }
        return $output;
    }
}
