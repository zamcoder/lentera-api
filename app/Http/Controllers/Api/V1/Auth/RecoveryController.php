<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\AuthResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RecoveryConfirmRequest;
use App\Http\Requests\Auth\RecoveryRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Support\JwtTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * RecoveryController (v1) — pemulihan dibantu server via email terdaftar (§1).
 * Pemulihan sandi login TIDAK memberi server akses ke jurnal E2E — device tetap
 * menurunkan kunci dari passphrase.
 */
class RecoveryController extends Controller
{
    use AuthResponses;

    public function __construct(private readonly OtpService $otp)
    {
    }

    /** POST /api/v1/auth/recovery — kirim kode pemulihan ke email terdaftar. */
    public function request(RecoveryRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];
        $user = User::where('email', $email)->first();
        $devCode = null;

        // Selalu balas sukses (jangan bocorkan email mana yang terdaftar).
        if ($user) {
            $code = $this->otp->issue($email, 'recover');
            $devCode = app()->environment('local', 'testing') ? $code : null;
        }

        return response()->json([
            'message' => 'Bila email terdaftar, kode pemulihan telah dikirim.',
            'dev_code' => $devCode,
        ]);
    }

    /** POST /api/v1/auth/recovery/confirm — verifikasi kode + set sandi baru. */
    public function confirm(RecoveryConfirmRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->otp->verify($data['email'], 'recover', $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode salah atau kedaluwarsa.']]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->password_hash = Hash::make($data['password']);
        $user->save();

        return $this->tokenResponse($user, JwtTokens::forApp($user));
    }
}
