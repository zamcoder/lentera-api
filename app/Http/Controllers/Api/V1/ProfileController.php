<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\AddEmailRequest;
use App\Http\Requests\Profile\AddPhoneRequest;
use App\Http\Requests\Profile\ConfirmEmailRequest;
use App\Http\Requests\Profile\ConfirmPhoneRequest;
use App\Http\Resources\UserResource;
use App\Models\AuthIdentity;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ProfileController (v1, §1) — "Lengkapi profil": tambah/ganti/hapus metode
 * masuk (email & nomor WhatsApp) pada akun yang sudah ada. Selalu dgn verifikasi
 * kepemilikan (OTP email / OTP WA). `kdf_salt` TIDAK PERNAH disentuh di sini —
 * menambah kontak tak boleh membuat cadangan E2E lama tak terbaca.
 */
class ProfileController extends Controller
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    /** POST /api/v1/profile/email — kirim kode 6 digit ke email (tambah/ganti). */
    public function requestEmail(AddEmailRequest $request): JsonResponse
    {
        $email = $this->normEmail($request->validated()['email']);
        $user = auth('api')->user();

        if ($user->email !== null && $this->normEmail($user->email) === $email) {
            throw ValidationException::withMessages(['email' => ['Email ini sudah terpasang di akunmu.']]);
        }
        $this->assertEmailFree($email, $user->id);

        $code = $this->otp->issue($email, 'add_email');

        return response()->json([
            'message' => 'Kode verifikasi dikirim ke email.',
            'dev_code' => app()->environment('local', 'testing') ? $code : null,
        ]);
    }

    /** POST /api/v1/profile/email/confirm — verifikasi & pasang email. */
    public function confirmEmail(ConfirmEmailRequest $request): JsonResponse
    {
        $data = $request->validated();
        $email = $this->normEmail($data['email']);
        $user = auth('api')->user();

        if (! $this->otp->verify($email, 'add_email', $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode salah atau kedaluwarsa.']]);
        }
        $this->assertEmailFree($email, $user->id);

        DB::transaction(function () use ($user, $email) {
            // Ganti: buang identitas email lama user ini, pasang yang baru.
            AuthIdentity::where('user_id', $user->id)->where('provider', 'email')->delete();
            $user->email = $email;           // kolom citext (unik, case-insensitive)
            $user->save();                   // kdf_salt TIDAK disentuh
            AuthIdentity::create([
                'user_id' => $user->id, 'provider' => 'email',
                'identifier' => $email, 'verified_at' => now(),
            ]);
        });

        return $this->userResponse($user);
    }

    /** POST /api/v1/profile/phone — kirim OTP WhatsApp (tambah/ganti nomor). */
    public function requestPhone(AddPhoneRequest $request): JsonResponse
    {
        $phone = $request->validated()['phone'];
        $user = auth('api')->user();

        if ($user->identities()->where('provider', 'phone')->where('identifier', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => ['Nomor ini sudah terpasang di akunmu.']]);
        }
        $this->assertPhoneFree($phone, $user->id);

        $code = $this->otp->issue($phone, 'add_phone');

        return response()->json([
            'message' => 'Kode verifikasi dikirim lewat WhatsApp.',
            'dev_code' => app()->environment('local', 'testing') ? $code : null,
        ]);
    }

    /** POST /api/v1/profile/phone/confirm — verifikasi & pasang nomor. */
    public function confirmPhone(ConfirmPhoneRequest $request): JsonResponse
    {
        $data = $request->validated();
        $phone = $data['phone'];
        $user = auth('api')->user();

        if (! $this->otp->verify($phone, 'add_phone', $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kode salah atau kedaluwarsa.']]);
        }
        $this->assertPhoneFree($phone, $user->id);

        DB::transaction(function () use ($user, $phone) {
            // Ganti: satu nomor aktif per user (buang yang lama, pasang baru).
            AuthIdentity::where('user_id', $user->id)->where('provider', 'phone')->delete();
            AuthIdentity::create([
                'user_id' => $user->id, 'provider' => 'phone',
                'identifier' => $phone, 'verified_at' => now(),
            ]);
        });

        return $this->userResponse($user);
    }

    /**
     * DELETE /api/v1/profile/identity — hapus satu metode masuk (email|phone),
     * asalkan MASIH ada minimal satu cara masuk tersisa.
     */
    public function removeIdentity(Request $request): JsonResponse
    {
        $provider = $request->validate([
            'provider' => ['required', 'in:email,phone'],
        ])['provider'];
        $user = auth('api')->user();

        $ofType = $user->identities()->where('provider', $provider)->count();
        if ($ofType === 0) {
            throw ValidationException::withMessages(['provider' => ['Metode ini belum terpasang.']]);
        }
        if ($user->identities()->count() - $ofType < 1) {
            throw ValidationException::withMessages([
                'provider' => ['Tidak bisa dihapus — sisakan minimal satu cara masuk.'],
            ]);
        }

        DB::transaction(function () use ($user, $provider) {
            $user->identities()->where('provider', $provider)->delete();
            if ($provider === 'email') {
                $user->email = null;         // kdf_salt & password TIDAK disentuh
                $user->save();
            }
        });

        return $this->userResponse($user);
    }

    // --- Helpers ---

    private function normEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Pastikan email belum dipakai akun LAIN (users.email atau identitas). */
    private function assertEmailFree(string $email, string $uid): void
    {
        $taken = User::where('email', $email)->where('id', '!=', $uid)->exists()
            || AuthIdentity::where('provider', 'email')->where('identifier', $email)
                ->where('user_id', '!=', $uid)->exists();

        if ($taken) {
            throw ValidationException::withMessages(['email' => ['Email ini sudah dipakai akun lain.']]);
        }
    }

    /** Pastikan nomor belum dipakai akun LAIN. */
    private function assertPhoneFree(string $phone, string $uid): void
    {
        $taken = AuthIdentity::where('provider', 'phone')->where('identifier', $phone)
            ->where('user_id', '!=', $uid)->exists();

        if ($taken) {
            throw ValidationException::withMessages(['phone' => ['Nomor ini sudah dipakai akun lain.']]);
        }
    }

    /** Respons objek user terbaru — bentuk sama dengan GET /me. */
    private function userResponse(User $user): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user->fresh()->load(['identities', 'vaultBackup'])),
        ]);
    }
}
