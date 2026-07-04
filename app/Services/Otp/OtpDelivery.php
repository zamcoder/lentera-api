<?php

namespace App\Services\Otp;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * OtpDelivery — mengirim kode OTP lewat kanal sesuai tujuan (§1):
 *  - purpose 'recover'   → EMAIL (identifier = alamat email)
 *  - purpose 'login_otp' → WHATSAPP (identifier = nomor HP)
 *
 * SMS tidak dipakai.
 */
class OtpDelivery
{
    private const TTL_MINUTES = 5;

    public function __construct(private readonly WhatsAppChannel $whatsapp)
    {
    }

    public function send(string $identifier, string $purpose, string $code): void
    {
        if ($purpose === 'recover') {
            Mail::to($identifier)->send(new OtpMail($code, self::TTL_MINUTES));

            return;
        }

        // login_otp (dan lainnya berbasis HP) → WhatsApp.
        $message = "Kode Lentera kamu: {$code}. Berlaku ".self::TTL_MINUTES
            .' menit. Jangan bagikan ke siapa pun.';
        $this->whatsapp->send($identifier, $message);
    }
}
