<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * GoogleTokenVerifier — memverifikasi ID token Google (Sign in with Google, §1).
 *
 * Memakai endpoint tokeninfo Google yang memvalidasi signature + kedaluwarsa di
 * sisi Google, lalu kita cek `iss`, `aud` (harus salah satu client ID kita), dan
 * ambil `sub`+`email`. Tanpa verifikasi ini, siapa pun bisa mengaku sebagai user
 * lain hanya dengan mengirim `sub` mentah.
 */
class GoogleTokenVerifier
{
    private const TOKENINFO = 'https://oauth2.googleapis.com/tokeninfo';

    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * @return array{sub: string, email: ?string, email_verified: bool}
     *
     * @throws RuntimeException bila token tidak valid / aud tak cocok.
     */
    public function verify(string $idToken): array
    {
        $allowed = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.google.client_ids'))
        )));

        if ($allowed === []) {
            throw new RuntimeException('GOOGLE_CLIENT_IDS belum dikonfigurasi di server.');
        }

        $resp = Http::timeout(10)->get(self::TOKENINFO, ['id_token' => $idToken]);
        if (! $resp->ok()) {
            throw new RuntimeException('ID token Google tidak valid atau kedaluwarsa.');
        }

        $claims = (array) $resp->json();

        if (! in_array($claims['iss'] ?? '', self::ISSUERS, true)) {
            throw new RuntimeException('Issuer token bukan Google.');
        }

        if (! in_array($claims['aud'] ?? '', $allowed, true)) {
            throw new RuntimeException('Audience (client ID) token tidak dikenal.');
        }

        if (empty($claims['sub'])) {
            throw new RuntimeException('Token Google tanpa subject (sub).');
        }

        $verified = ($claims['email_verified'] ?? false);

        return [
            'sub' => (string) $claims['sub'],
            'email' => $claims['email'] ?? null,
            'email_verified' => $verified === true || $verified === 'true',
        ];
    }
}
