<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\AuthResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Services\OtpService;
use App\Support\JwtTokens;
use App\Support\Pseudonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * OtpController (v1) — masuk via nomor HP tanpa sandi (§1 metode B).
 */
class OtpController extends Controller
{
    use AuthResponses;

    public function __construct(private readonly OtpService $otp)
    {
    }

    /** POST /api/v1/auth/otp/request — kirim kode ke nomor HP. */
    public function request(OtpRequest $request): JsonResponse
    {
        $code = $this->otp->issue($request->validated()['phone'], 'login_otp');

        return response()->json([
            'message' => 'Kode dikirim.',
            'dev_code' => app()->environment('local', 'testing') ? $code : null,
        ]);
    }

    /** POST /api/v1/auth/otp/verify — verifikasi kode → buat/masuk akun. */
    public function verify(OtpVerifyRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->otp->verify($data['phone'], 'login_otp', $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode salah atau kedaluwarsa.']]);
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

        return $this->tokenResponse($user, JwtTokens::forApp($user));
    }
}
