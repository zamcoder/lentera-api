<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Pseudonym;
use App\Support\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * OtpController — masuk via nomor HP tanpa kata sandi (§A2 metode B).
 * Dua langkah: minta kode → verifikasi kode.
 */
class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    /** POST /auth/otp/request — kirim kode ke nomor HP. */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
        ]);

        $code = $this->otp->issue($data['phone'], 'login_otp');

        return response()->json([
            'message' => 'Kode dikirim.',
            // Hanya di lokal untuk memudahkan pengujian — jangan pernah di produksi.
            'dev_code' => app()->environment('local', 'testing') ? $code : null,
        ]);
    }

    /** POST /auth/otp/verify — verifikasi kode → buat/masuk akun. */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        if (! $this->otp->verify($data['phone'], 'login_otp', $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Kode salah atau kedaluwarsa.'],
            ]);
        }

        $user = DB::transaction(function () use ($data) {
            $identity = AuthIdentity::where('provider', 'phone')
                ->where('identifier', $data['phone'])
                ->first();

            if ($identity) {
                return $identity->user;
            }

            $user = User::create([
                'handle' => Pseudonym::unique(),
                'role' => 'user',
                'status' => 'active',
            ]);

            AuthIdentity::create([
                'user_id' => $user->id,
                'provider' => 'phone',
                'identifier' => $data['phone'],
                'verified_at' => now(),
            ]);

            return $user;
        });

        $token = $user->createToken('app', [TokenAbilities::APP])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'handle' => $user->handle,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }
}
