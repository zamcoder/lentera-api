<?php

use App\Http\Controllers\Api\SubscriberController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OAuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\RecoveryController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Lentera — semua di /api/v1 (JSON, error {message, errors})
|--------------------------------------------------------------------------
| Auth JWT (tymon/jwt-auth). Domain jurnal E2E & komunitas menyusul per fase.
| Referensi kebenaran: API_REQUIREMENTS.md, lib/data/models.dart.
*/

// ---------- Waitlist landing page (temanlentera.id) — di /api (bukan v1) ----------
Route::post('/subscribe', [SubscriberController::class, 'store'])->middleware('throttle:10,1');
Route::get('/subscribers', [SubscriberController::class, 'index'])->middleware('auth:api');

Route::prefix('v1')->group(function () {

    // ---------- Health (kickoff) ----------
    Route::get('/health', HealthController::class);

    // ---------- Auth & Akun (§1) ----------
    Route::prefix('auth')->group(function () {
        // Publik + rate limit (anti brute-force).
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/oauth', [OAuthController::class, 'callback']);
            Route::post('/otp/request', [OtpController::class, 'request']);
            Route::post('/otp/verify', [OtpController::class, 'verify']);
            Route::post('/recovery', [RecoveryController::class, 'request']);
            Route::post('/recovery/confirm', [RecoveryController::class, 'confirm']);
            // Refresh: token dibaca dari header (boleh kedaluwarsa, dalam refresh_ttl).
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

        // Verifikasi 2FA: butuh token JWT (pending untuk admin, atau token user ber-2FA).
        Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->middleware('auth:api');

        // Sesudah login (token app apa pun).
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/2fa/setup', [TwoFactorController::class, 'setup']);
            Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
            Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
        });
    });

    Route::get('/me', [MeController::class, 'show'])->middleware('auth:api');

    // ---------- Sync / Vault (§2) — JWT ----------
    require __DIR__.'/api_vault.php';

    // ---------- Orang / People (§3) — JWT, E2E ----------
    require __DIR__.'/api_people.php';

    // ---------- Momen/Interaksi (§4) + Media (§5) — JWT, E2E ----------
    require __DIR__.'/api_interactions.php';

    // ---------- Mood, Statistik & Rekap (§6) — JWT ----------
    require __DIR__.'/api_stats.php';

    // ---------- Pengaturan/Notifikasi (§12) + Keselamatan (§11) — JWT ----------
    require __DIR__.'/api_settings.php';

    // ---------- Komunitas: Feed & Post (§7) — JWT ----------
    require __DIR__.'/api_community.php';

    // ---------- Moderasi & konsol (§10 / §A5-A6) — JWT ----------
    require __DIR__.'/api_moderation.php';
});
