<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppChannel — kirim pesan WhatsApp lewat gateway (§1 OTP login HP).
 * Provider: 'fonnte' (gateway populer ID) atau 'cloud' (Meta WhatsApp Cloud API).
 * Bila provider 'log' atau token kosong → tulis ke log (dev / belum dikonfigurasi).
 */
class WhatsAppChannel
{
    public function send(string $phone, string $message): void
    {
        $cfg = config('lentera.whatsapp');
        $provider = $cfg['provider'] ?? 'log';

        if ($provider === 'log' || empty($cfg['token'])) {
            Log::info("WA (stub) ke {$phone}: {$message}");

            return;
        }

        try {
            match ($provider) {
                'fonnte' => $this->fonnte($cfg, $phone, $message),
                'cloud' => $this->cloud($cfg, $phone, $message),
                default => Log::warning("WA provider tak dikenal: {$provider}"),
            };
        } catch (\Throwable $e) {
            Log::warning('WA gagal kirim: '.$e->getMessage());
        }
    }

    /** Fonnte — https://docs.fonnte.com (target + message + Authorization: token). */
    private function fonnte(array $cfg, string $phone, string $message): void
    {
        $endpoint = $cfg['endpoint'] ?: 'https://api.fonnte.com/send';
        Http::withHeaders(['Authorization' => $cfg['token']])
            ->asForm()->timeout(15)
            ->post($endpoint, ['target' => $this->normalize($phone), 'message' => $message])
            ->throw();
    }

    /** Meta WhatsApp Cloud API — graph.facebook.com/{phone_id}/messages. */
    private function cloud(array $cfg, string $phone, string $message): void
    {
        $endpoint = $cfg['endpoint'] ?: "https://graph.facebook.com/v20.0/{$cfg['phone_id']}/messages";
        Http::withToken($cfg['token'])->timeout(15)->post($endpoint, [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalize($phone),
            'type' => 'text',
            'text' => ['body' => $message],
        ])->throw();
    }

    /** Normalisasi nomor ke digit saja (mis. +6281.. → 6281..). App mengirim E.164. */
    private function normalize(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
