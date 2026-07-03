<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;

/**
 * OtpService — kode sekali-pakai untuk login HP & pemulihan. Kode disimpan
 * sebagai hash SHA-256 (tak pernah plaintext di DB). Di lingkungan lokal, kode
 * dikembalikan agar mudah diuji; di produksi dikirim via SMS/email (belum
 * terpasang — dicatat ke log sebagai placeholder).
 */
class OtpService
{
    private const TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    /** Terbitkan kode baru untuk identifier+purpose. Mengembalikan kode plaintext. */
    public function issue(string $identifier, string $purpose): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'identifier' => $identifier,
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        // TODO produksi: kirim via SMS/email provider.
        Log::info("OTP {$purpose} untuk {$identifier}: {$code}");

        return $code;
    }

    /** Verifikasi kode; menandai consumed bila cocok. */
    public function verify(string $identifier, string $purpose, string $code): bool
    {
        $otp = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $otp || ! $otp->isValid() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->increment('attempts');

        if (! hash_equals($otp->code_hash, hash('sha256', $code))) {
            return false;
        }

        $otp->consumed_at = now();
        $otp->save();

        return true;
    }
}
