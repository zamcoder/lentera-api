<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\AuthResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OAuthRequest;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Support\JwtTokens;
use App\Support\Pseudonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * OAuthController (v1) — masuk via Google / Apple (§1 metode C & D).
 *
 * CATATAN: verifikasi id_token ke penyedia (JWKS) belum terpasang; di lokal
 * `sub` diterima apa adanya. Di produksi WAJIB verifikasi signature dulu.
 */
class OAuthController extends Controller
{
    use AuthResponses;

    public function callback(OAuthRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $identity = AuthIdentity::where('provider', $data['provider'])
                ->where('identifier', $data['sub'])
                ->first();

            if ($identity) {
                return $identity->user;
            }

            $user = User::create([
                'handle' => Pseudonym::unique(),
                'email' => $data['email'] ?? null,
                'role' => 'user',
                'status' => 'active',
            ]);

            AuthIdentity::create([
                'user_id' => $user->id,
                'provider' => $data['provider'],
                'identifier' => $data['sub'],
                'verified_at' => now(),
            ]);

            return $user;
        });

        return $this->tokenResponse($user, JwtTokens::forApp($user));
    }
}
