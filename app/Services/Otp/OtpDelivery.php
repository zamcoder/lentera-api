<?php

namespace App\Services\Otp;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * OtpDelivery — mengirim kode OTP lewat kanal sesuai tujuan (§1):
 *  - purpose 'recover' / 'add_email' → EMAIL (identifier = alamat email)
 *  - purpose 'login_otp' / 'add_phone' → WHATSAPP (identifier = nomor HP)
 *
 * SMS tidak dipakai.
 */
class OtpDelivery
{
    private const TTL_MINUTES = 5;

    /** Tujuan yang dikirim lewat email (sisanya lewat WhatsApp). */
    private const EMAIL_PURPOSES = ['recover', 'add_email'];

    public function __construct(private readonly WhatsAppChannel $whatsapp)
    {
    }

    public function send(string $identifier, string $purpose, string $code): void
    {
        if (in_array($purpose, self::EMAIL_PURPOSES, true)) {
            Mail::to($identifier)->send(new OtpMail($code, self::TTL_MINUTES));

            return;
        }

        // login_otp / add_phone (dan lainnya berbasis HP) → WhatsApp.
        $message = "Kode Lentera kamu: {$code}. Berlaku ".self::TTL_MINUTES
            .' menit. Jangan bagikan ke siapa pun.';
        $this->whatsapp->send($identifier, $message);
    }
}
