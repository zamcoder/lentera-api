<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RecoveryController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Support\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Lentera
|--------------------------------------------------------------------------
| Grup: Auth (/auth/*) · Sync (/vault/*) · Community · Moderation (/mod/*)
| Referensi: Rencana Produk §08, Handoff Doc §6.
*/

// ===================== AUTH (§A2) =====================
Route::prefix('auth')->group(function () {
    // Publik + rate limit ketat (anti brute-force).
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/otp/request', [OtpController::class, 'request']);
        Route::post('/otp/verify', [OtpController::class, 'verify']);
        Route::post('/oauth', [OAuthController::class, 'callback']);
        Route::post('/recover', [RecoveryController::class, 'request']);
        Route::post('/recover/confirm', [RecoveryController::class, 'confirm']);
    });

    // Verifikasi 2FA: butuh token "pending" (ability 2fa:pending).
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])
        ->middleware(['auth:sanctum', 'abilities:'.TokenAbilities::TWO_FA_PENDING]);

    // Sesudah login (token app apa pun).
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/2fa/setup', [TwoFactorController::class, 'setup']);
        Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
    });
});

// ===================== SYNC / VAULT (§A3) =====================
// (didefinisikan di bagian Sync)

// ===================== COMMUNITY (§A4) =====================
// (didefinisikan di bagian Community)

// ===================== MODERATION (§A5/§A6) =====================
// (didefinisikan di bagian Moderation)

require __DIR__.'/api_vault.php';
require __DIR__.'/api_community.php';
require __DIR__.'/api_moderation.php';
