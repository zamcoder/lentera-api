<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\AuthResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorCodeRequest;
use App\Services\TotpService;
use App\Support\JwtTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * TwoFactorController (v1) — TOTP 2FA (§1). Untuk konsol admin, verifikasi 2FA
 * menghasilkan token JWT ber-scope `mod`. Untuk pengguna biasa ber-2FA,
 * verifikasi menghasilkan token app biasa.
 */
class TwoFactorController extends Controller
{
    use AuthResponses;

    public function __construct(private readonly TotpService $totp)
    {
    }

    /** POST /api/v1/auth/2fa/setup — buat secret baru (belum aktif). Admin. */
    public function setup(): JsonResponse
    {
        $user = auth('api')->user();
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

    /** POST /api/v1/auth/2fa/enable — verifikasi kode pertama & aktifkan. Admin. */
    public function enable(TwoFactorCodeRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $this->assertAdmin($user);

        if (! $user->totp_secret_enc) {
            throw ValidationException::withMessages(['code' => ['Jalankan setup 2FA dulu.']]);
        }

        $secret = Crypt::decryptString($user->totp_secret_enc);
        if (! $this->totp->verify($secret, $request->validated()['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode tidak valid.']]);
        }

        $user->totp_enabled = true;
        $user->save();

        return $this->tokenResponse($user, JwtTokens::forConsole($user));
    }

    /** POST /api/v1/auth/2fa/verify — tukar token pending → token final. */
    public function verify(TwoFactorCodeRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user->totp_enabled || ! $user->totp_secret_enc) {
            throw ValidationException::withMessages(['code' => ['2FA belum aktif.']]);
        }

        $secret = Crypt::decryptString($user->totp_secret_enc);
        if (! $this->totp->verify($secret, $request->validated()['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode tidak valid.']]);
        }

        // Admin → token konsol (scope mod); pengguna biasa → token app.
        $token = $user->isAdmin() ? JwtTokens::forConsole($user) : JwtTokens::forApp($user);

        return $this->tokenResponse($user, $token);
    }

    /** POST /api/v1/auth/2fa/disable — matikan 2FA (butuh kode aktif). Admin. */
    public function disable(TwoFactorCodeRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $this->assertAdmin($user);

        $secret = $user->totp_secret_enc ? Crypt::decryptString($user->totp_secret_enc) : null;
        if (! $secret || ! $this->totp->verify($secret, $request->validated()['code'])) {
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
