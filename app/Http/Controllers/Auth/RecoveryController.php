<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * RecoveryController — pemulihan akses dibantu server (§04/§05).
 *
 * TRADE-OFF PRIVASI (§05): server memegang jalur pemulihan lewat email/HP.
 * Ini menurunkan kemurnian E2E sedikit demi mencegah pengguna terkunci
 * selamanya. Jurnal ter-escrow tetap terenkripsi; pemulihan sandi login
 * TIDAK memberi server akses ke isi jurnal — device tetap menurunkan kunci
 * dari passphrase. Pengguna boleh memilih mode "tanpa pemulihan" (lihat Vault).
 */
class RecoveryController extends Controller
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    /** POST /auth/recover — minta kode pemulihan ke email terdaftar. */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Selalu balas sukses (jangan bocorkan email mana yang terdaftar).
        $user = User::where('email', $data['email'])->first();
        $devCode = null;

        if ($user) {
            $code = $this->otp->issue($data['email'], 'recover');
            $devCode = app()->environment('local', 'testing') ? $code : null;
        }

        return response()->json([
            'message' => 'Bila email terdaftar, kode pemulihan telah dikirim.',
            'dev_code' => $devCode,
        ]);
    }

    /** POST /auth/recover/confirm — verifikasi kode + set sandi baru. */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! $this->otp->verify($data['email'], 'recover', $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Kode salah atau kedaluwarsa.'],
            ]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->password_hash = Hash::make($data['password']);
        $user->save();

        // Cabut semua token lama demi keamanan pasca-pemulihan.
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Kata sandi diperbarui.',
            'token' => $user->createToken('app', [TokenAbilities::APP])->plainTextToken,
        ]);
    }
}
