<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Support\Pseudonym;
use App\Support\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * OAuthController — masuk via Google / Apple (§A2 metode C & D).
 *
 * CATATAN INTEGRASI: verifikasi id_token ke penyedia (JWKS Google / Apple)
 * belum terpasang. Di lokal, `sub` (subject) diterima apa adanya untuk
 * pengembangan. Di produksi WAJIB memverifikasi signature id_token dulu.
 */
class OAuthController extends Controller
{
    public function callback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:google,apple'],
            'sub' => ['required', 'string', 'max:255'],   // subject unik dari penyedia
            'email' => ['nullable', 'email'],
        ]);

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

        return response()->json([
            'token' => $user->createToken('app', [TokenAbilities::APP])->plainTextToken,
            'user' => [
                'id' => $user->id,
                'handle' => $user->handle,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }
}
