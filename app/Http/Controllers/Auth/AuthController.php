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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * AuthController — jalur masuk klasik email + kata sandi (§A2 metode A).
 * Argon2id via config/hashing. Token akses lewat Sanctum.
 */
class AuthController extends Controller
{
    /**
     * POST /auth/register — daftar akun email + simpan kdf_salt (Handoff API).
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'handle' => ['nullable', 'string', 'max:40', 'unique:users,handle'],
            // kdf_salt dibuat di device untuk menurunkan kunci E2E; dikirim base64.
            'kdf_salt' => ['nullable', 'string'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'handle' => $data['handle'] ?? Pseudonym::unique(),
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                // Bila device tak mengirim salt, buat cadangan acak (device tetap
                // yang memegang passphrase — server tak bisa menurunkan kunci).
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

        return $this->tokenResponse($user, 201);
    }

    /**
     * POST /auth/login — verifikasi sandi. Untuk admin ber-2FA, kembalikan token
     * "pending" yang hanya bisa dipakai memverifikasi 2FA (§A6 admin gate).
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password_hash || ! Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        if ($user->status === 'blocked') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak dapat masuk.'],
            ]);
        }

        // Konsol admin wajib lewat 2FA (§A2/§A6).
        if ($user->isAdmin()) {
            if ($user->totp_enabled) {
                $pending = $user->createToken('pending-2fa', [TokenAbilities::TWO_FA_PENDING])->plainTextToken;

                return response()->json([
                    'two_factor_required' => true,
                    'pending_token' => $pending,
                ]);
            }

            // Admin belum pasang 2FA → token app + wajib setup sebelum akses /mod.
            return response()->json([
                'two_factor_setup_required' => true,
                'token' => $user->createToken('setup', [TokenAbilities::APP])->plainTextToken,
                'user' => $this->userPayload($user),
            ]);
        }

        return $this->tokenResponse($user);
    }

    /** GET /auth/me — profil pengguna terautentikasi. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /** POST /auth/logout — cabut token saat ini. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sampai jumpa.']);
    }

    /** Respons standar: token app + profil. */
    protected function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('app', [TokenAbilities::APP])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], $status);
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'handle' => $user->handle,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'totp_enabled' => (bool) $user->totp_enabled,
        ];
    }
}
