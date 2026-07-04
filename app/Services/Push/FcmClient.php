<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * FcmClient — kirim push via Firebase Cloud Messaging HTTP v1.
 *
 * Autentikasi memakai service-account (OAuth2 JWT bearer): JWT ditandatangani
 * RS256 dengan private key SA, ditukar jadi access token (di-cache ~55 menit),
 * lalu memanggil endpoint FCM v1. Tanpa dependensi eksternal.
 */
class FcmClient
{
    private array $sa;

    public function __construct()
    {
        $path = (string) config('lentera.push.fcm_credentials');
        $this->sa = ($path && is_file($path)) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    }

    public function isConfigured(): bool
    {
        return isset($this->sa['client_email'], $this->sa['private_key'], $this->sa['project_id']);
    }

    /**
     * Kirim ke satu device token. Mengembalikan true bila terkirim; false bila
     * token tak valid (perlu dihapus) atau gagal.
     */
    public function sendToToken(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $projectId = $this->sa['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $res = Http::withToken($this->accessToken())->timeout(20)->post($url, [
            'message' => [
                'token' => $deviceToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => array_map('strval', $data),
            ],
        ]);

        // Token tak terdaftar/kedaluwarsa → caller sebaiknya menghapusnya.
        if (in_array($res->status(), [400, 404], true)) {
            return false;
        }

        return $res->successful();
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            $now = time();
            $claims = [
                'iss' => $this->sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $this->sa['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $jwt = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
                .'.'.$this->b64(json_encode($claims));

            openssl_sign($jwt, $signature, $this->sa['private_key'], OPENSSL_ALGO_SHA256);
            $assertion = $jwt.'.'.$this->b64($signature);

            $res = Http::asForm()->post($claims['aud'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])->throw();

            return $res->json('access_token');
        });
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
