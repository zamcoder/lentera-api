<?php

use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
| LENGKAPI PROFIL (§1) — tambah/ganti/hapus metode masuk (email & nomor WA)
| pada akun yang sudah ada. Butuh JWT. Verifikasi kepemilikan via OTP.
| kdf_salt tak pernah berubah (cadangan E2E lama tetap terbaca).
*/
Route::middleware('auth:api')->group(function () {
    // Kirim OTP (email/WA) — throttle ketat agar tak jadi alat spam.
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('/profile/email', [ProfileController::class, 'requestEmail']);
        Route::post('/profile/phone', [ProfileController::class, 'requestPhone']);
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/profile/email/confirm', [ProfileController::class, 'confirmEmail']);
        Route::post('/profile/phone/confirm', [ProfileController::class, 'confirmPhone']);
    });

    Route::delete('/profile/identity', [ProfileController::class, 'removeIdentity']);
});
