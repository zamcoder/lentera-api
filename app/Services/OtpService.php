<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Services\Otp\OtpDelivery;

/**
 * OtpService — kode sekali-pakai untuk login HP (WhatsApp) & pemulihan (Email).
 * Kode disimpan sebagai hash SHA-256 (tak pernah plaintext di DB). Pengiriman
 * ditangani OtpDelivery (email/WA); SMS tidak dipakai.
 */
class OtpService
{
    private const TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly OtpDelivery $delivery)
    {
    }

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

        $this->delivery->send($identifier, $purpose, $code);

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
