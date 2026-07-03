<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use App\Support\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * TwoFactorController — TOTP 2FA untuk konsol admin (§A2/§A6).
 *
 * Alur: setup (buat secret) → enable (verifikasi kode pertama, aktifkan) →
 * sejak itu login admin menghasilkan token "pending" yang ditingkatkan jadi
 * token ber-ability 'mod' lewat verify. Secret disimpan terenkripsi (APP_KEY)
 * di kolom BYTEA totp_secret_enc.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TotpService $totp)
    {
    }

    /** POST /auth/2fa/setup — buat secret baru (belum aktif). */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertAdmin($user);

        $secret = $this->totp->generateSecret();
        $user->totp_secret_enc = Crypt::encryptString($secret);
        $user->totp_enabled = false;
        $user->save();

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $this->totp->provisioningUri($secret, $user->email ?? $user->handle),
            'message' => 'Pindai QR di aplikasi authenticator, lalu verifikasi untuk mengaktifkan.',
        ]);
    }

    /** POST /auth/2fa/enable — verifikasi kode pertama & aktifkan 2FA. */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertAdmin($user);

        $data = $request->validate(['code' => ['required', 'string']]);

        if (! $user->totp_secret_enc) {
            throw ValidationException::withMessages(['code' => ['Jalankan setup 2FA dulu.']]);
        }

        $secret = Crypt::decryptString($user->totp_secret_enc);
        if (! $this->totp->verify($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode tidak valid.']]);
        }

        $user->totp_enabled = true;
        $user->save();

        // Cabut token lama, terbitkan token konsol penuh (ability mod).
        $user->tokens()->delete();

        return response()->json([
            'message' => '2FA aktif.',
            'token' => $user->createToken('console', [TokenAbilities::APP, TokenAbilities::MOD])->plainTextToken,
        ]);
    }

    /** POST /auth/2fa/verify — tukar token pending menjadi token konsol. */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertAdmin($user);

        $data = $request->validate(['code' => ['required', 'string']]);

        if (! $user->totp_enabled || ! $user->totp_secret_enc) {
            throw ValidationException::withMessages(['code' => ['2FA belum aktif.']]);
        }

        $secret = Crypt::decryptString($user->totp_secret_enc);
        if (! $this->totp->verify($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode tidak valid.']]);
        }

        // Buang token pending saat ini, terbitkan token konsol penuh.
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => '2FA terverifikasi.',
            'token' => $user->createToken('console', [TokenAbilities::APP, TokenAbilities::MOD])->plainTextToken,
            'user' => [
                'id' => $user->id,
                'handle' => $user->handle,
                'role' => $user->role,
            ],
        ]);
    }

    /** POST /auth/2fa/disable — matikan 2FA (butuh kode aktif). */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertAdmin($user);

        $data = $request->validate(['code' => ['required', 'string']]);

        $secret = $user->totp_secret_enc ? Crypt::decryptString($user->totp_secret_enc) : null;
        if (! $secret || ! $this->totp->verify($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode tidak valid.']]);
        }

        $user->totp_enabled = false;
        $user->totp_secret_enc = null;
        $user->save();

        return response()->json(['message' => '2FA dinonaktifkan.']);
    }

    private function assertAdmin($user): void
    {
        abort_unless($user && $user->isAdmin(), 403, 'Hanya untuk admin.');
    }
}
