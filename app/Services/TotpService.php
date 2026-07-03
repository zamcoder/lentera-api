<?php

namespace App\Services;

/**
 * TotpService — TOTP RFC 6238 (SHA1, 6 digit, langkah 30 detik) tanpa dependensi
 * eksternal. Dipakai 2FA konsol admin (§A2/§A6). Secret dalam Base32.
 */
class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGO = 'sha1';

    /** Buat secret Base32 acak (default 160-bit). */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    /** Kode TOTP untuk waktu tertentu (default sekarang). */
    public function code(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = intdiv($timestamp, self::PERIOD);
        $binCounter = pack('N*', 0) . pack('N*', $counter); // 64-bit big-endian
        $key = $this->base32Decode($secret);

        $hash = hash_hmac(self::ALGO, $binCounter, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verifikasi kode dengan toleransi ±$window langkah (jam device bisa geser).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            $candidate = $this->code($secret, $now + ($i * self::PERIOD));
            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /** URI otpauth:// untuk QR di aplikasi authenticator. */
    public function provisioningUri(string $secret, string $account, string $issuer = 'Lentera'): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    // --- Base32 (RFC 4648, tanpa padding) ---

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out = '';
        $bits = 0;
        $value = 0;
        foreach (str_split($data) as $char) {
            $value = ($value << 8) | ord($char);
            $bits += 8;
            while ($bits >= 5) {
                $out .= $alphabet[($value >> ($bits - 5)) & 0x1F];
                $bits -= 5;
            }
        }
        if ($bits > 0) {
            $out .= $alphabet[($value << (5 - $bits)) & 0x1F];
        }

        return $out;
    }

    private function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        $out = '';
        $bits = 0;
        $value = 0;
        foreach (str_split($b32) as $char) {
            $value = ($value << 5) | strpos($alphabet, $char);
            $bits += 5;
            if ($bits >= 8) {
                $out .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $out;
    }
}
