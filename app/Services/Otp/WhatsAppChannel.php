<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppChannel — kirim pesan WhatsApp lewat gateway (§1 OTP login HP).
 * Provider:
 *   'gowa'   — go-whatsapp-web-multidevice (self-host, whatsmeow) via REST
 *   'fonnte' — gateway populer ID
 *   'cloud'  — Meta WhatsApp Cloud API
 *   'log'    — dev / belum dikonfigurasi → tulis ke log
 */
class WhatsAppChannel
{
    public function send(string $phone, string $message): void
    {
        $cfg = config('lentera.whatsapp');
        $provider = $cfg['provider'] ?? 'log';

        if ($provider === 'log') {
            Log::info("WA (stub) ke {$phone}: {$message}");

            return;
        }

        try {
            match ($provider) {
                'gowa' => $this->gowa($cfg, $phone, $message),
                'fonnte' => $this->fonnte($cfg, $phone, $message),
                'cloud' => $this->cloud($cfg, $phone, $message),
                default => Log::warning("WA provider tak dikenal: {$provider}"),
            };
        } catch (\Throwable $e) {
            Log::warning('WA gagal kirim: '.$e->getMessage());
        }
    }

    /**
     * go-whatsapp-web-multidevice (aldinokemal) — POST {base}/send/message
     * body {phone, message}. Auth: Basic (username:password). Nomor personal
     * cukup digit + kode negara (mis. 6281...), API menambah JID sendiri.
     */
    private function gowa(array $cfg, string $phone, string $message): void
    {
        $base = rtrim($cfg['endpoint'] ?: 'http://127.0.0.1:3000', '/');
        $req = Http::timeout(20)->acceptJson();
        if (! empty($cfg['username'])) {
            $req = $req->withBasicAuth($cfg['username'], (string) ($cfg['password'] ?? ''));
        }
        $req->post($base.'/send/message', [
            'phone' => $this->normalize($phone),
            'message' => $message,
        ])->throw();
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
