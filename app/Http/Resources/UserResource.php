<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — bentuk profil akun untuk /me & respons auth (§1 API_REQUIREMENTS:
 * "profil akun + metode login + status sinkron").
 *
 * kdf_salt DIKEMBALIKAN (base64): salt KDF bukan rahasia — keamanan berasal dari
 * passphrase, dan device WAJIB memperolehnya untuk menurunkan kunci yang sama di
 * perangkat baru / setelah re-install. totp_secret_enc tetap TIDAK pernah bocor.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'handle' => $this->handle,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'totp_enabled' => (bool) $this->totp_enabled,
            // Salt untuk Argon2id di device (base64). Server tak pernah menurunkan kunci.
            'kdf_salt' => $this->kdf_salt ? base64_encode($this->kdf_salt) : null,
            // Metode login terhubung (email/phone/google/apple).
            'providers' => $this->whenLoaded(
                'identities',
                fn () => $this->identities->pluck('provider')->unique()->values(),
                [],
            ),
            // Status sinkron vault E2E (server hanya tahu ada/tidak + kapan).
            'sync' => [
                'enabled' => (bool) $this->sync_on,
                'has_backup' => (bool) $this->whenLoaded('vaultBackup', fn () => (bool) $this->vaultBackup, false),
                'last_synced_at' => $this->whenLoaded('vaultBackup', fn () => optional($this->vaultBackup)->updated_at, null),
            ],
        ];
    }
}
