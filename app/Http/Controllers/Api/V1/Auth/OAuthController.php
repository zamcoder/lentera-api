<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\AuthResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OAuthRequest;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Services\Auth\GoogleTokenVerifier;
use App\Support\JwtTokens;
use App\Support\Pseudonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * OAuthController (v1) — masuk via Google (§1 metode C).
 *
 * ID token dari client DIVERIFIKASI ke penyedia (signature + aud) sebelum
 * dipercaya. `sub`/`email` diambil dari token terverifikasi, bukan dari body
 * mentah — mencegah pemalsuan identitas. Apple menyusul (verifier terpisah).
 */
class OAuthController extends Controller
{
    use AuthResponses;

    public function callback(OAuthRequest $request, GoogleTokenVerifier $google): JsonResponse
    {
        $data = $request->validated();

        if ($data['provider'] !== 'google') {
            return response()->json(['message' => 'Provider ini belum didukung.'], 422);
        }

        try {
            $claims = $google->verify($data['id_token']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Verifikasi Google gagal: '.$e->getMessage()], 401);
        }

        $user = DB::transaction(function () use ($claims) {
            $identity = AuthIdentity::where('provider', 'google')
                ->where('identifier', $claims['sub'])
                ->first();

            if ($identity) {
                return $identity->user;
            }

            $user = User::create([
                'handle' => Pseudonym::unique(),
                'email' => $claims['email'] ?? null,
                // kdf_salt: device Google tak pernah membuatnya saat register.
                // Server generate acak (opsi A) agar user Google bisa menurunkan
                // kunci E2E dari passphrase yang di-set app pasca-login pertama.
                'kdf_salt' => random_bytes(16),
                'role' => 'user',
                'status' => 'active',
            ]);

            AuthIdentity::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'identifier' => $claims['sub'],
                'verified_at' => now(),
            ]);

            return $user;
        });

        return $this->tokenResponse($user, JwtTokens::forApp($user));
    }
}
