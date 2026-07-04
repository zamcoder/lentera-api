<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\AuthResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Support\JwtTokens;
use App\Support\Pseudonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

/**
 * AuthController (v1) — email + kata sandi (§1). Argon2id, token JWT.
 * Konsol admin wajib 2FA sebelum memperoleh token ber-scope `mod`.
 */
class AuthController extends Controller
{
    use AuthResponses;

    /** POST /api/v1/auth/register — daftar + simpan kdf_salt (E2E). */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'handle' => $data['handle'] ?? Pseudonym::unique(),
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'kdf_salt' => isset($data['kdf_salt'])
                    ? base64_decode($data['kdf_salt'])
                    : random_bytes(16),
                'role' => 'user',
                'status' => 'active',
            ]);

            AuthIdentity::create([
                'user_id' => $user->id,
                'provider' => 'email',
                'identifier' => $data['email'],
                'verified_at' => now(),
            ]);

            return $user;
        });

        return $this->tokenResponse($user, JwtTokens::forApp($user), 201);
    }

    /** POST /api/v1/auth/login — verifikasi sandi → JWT (lanjut 2FA bila aktif). */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password_hash || ! Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages(['email' => ['Email atau kata sandi salah.']]);
        }

        if ($user->status === 'blocked') {
            throw ValidationException::withMessages(['email' => ['Akun ini tidak dapat masuk.']]);
        }

        // 2FA opsional untuk semua akun; wajib untuk konsol admin (§A2/§A6).
        if ($user->totp_enabled) {
            return response()->json([
                'two_factor_required' => true,
                'pending_token' => JwtTokens::pending($user),
            ]);
        }

        // Admin belum pasang 2FA → beri token app + tanda wajib setup.
        if ($user->isAdmin()) {
            $user->load(['identities', 'vaultBackup']);

            return response()->json([
                'two_factor_setup_required' => true,
                'token' => JwtTokens::forApp($user),
                'user' => new UserResource($user),
            ]);
        }

        return $this->tokenResponse($user, JwtTokens::forApp($user));
    }

    /** POST /api/v1/auth/logout — cabut (blacklist) token JWT saat ini. */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Sampai jumpa.']);
    }

    /**
     * POST /api/v1/auth/refresh — tukar token (yang masih dalam jendela
     * refresh_ttl, boleh sudah kedaluwarsa) dengan token app baru. Token lama
     * di-blacklist.
     *
     * Catatan keamanan: refresh menghasilkan token APP (tanpa klaim `mod`) —
     * scope konsol hanya bisa diperoleh lewat verifikasi 2FA, tak bisa via refresh.
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();          // blacklist lama, terbitkan baru
            $user = JWTAuth::setToken($newToken)->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            throw ValidationException::withMessages([
                'token' => ['Token tidak dapat diperbarui, silakan login ulang.'],
            ]);
        }

        return $this->tokenResponse($user, $newToken);
    }
}
