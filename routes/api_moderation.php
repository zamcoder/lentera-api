<?php

use App\Http\Controllers\Api\SubscriberController;
use App\Http\Controllers\Api\V1\Community\ModerationController;
use App\Http\Controllers\Api\V1\Community\ReportController;
use App\Http\Controllers\Console\AccountsController;
use App\Http\Controllers\Console\MetricsController;
use App\Http\Controllers\Console\QueueController;
use App\Http\Controllers\Console\ReportsController;
use App\Http\Controllers\Console\TermsController;
use Illuminate\Support\Facades\Route;

/*
| MODERATION (§10 / §A5-A6) — di /api/v1, auth JWT.
| - POST /reports + GET /moderation/banned-terms: semua pengguna terautentikasi (§10).
| - /mod/*: gerbang admin — token JWT ber-scope 'mod' (hanya pasca-2FA) + role admin.
*/

// Pelaporan pengguna & sinkron daftar moderasi (mobile).
Route::middleware('auth:api')->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/moderation/banned-terms', [ModerationController::class, 'bannedTerms']);
});

// Konsol moderasi — berlapis: JWT → middleware moderator (role admin + 2FA + scope mod).
Route::middleware(['auth:api', 'moderator'])
    ->prefix('mod')
    ->group(function () {
        // Antrean moderasi (§B3).
        Route::get('/queue', [QueueController::class, 'index']);
        Route::post('/action', [QueueController::class, 'action']);

        // Laporan (§B4).
        Route::get('/reports', [ReportsController::class, 'index']);
        Route::post('/reports/action', [ReportsController::class, 'action']);

        // Kata terlarang (§B5).
        Route::get('/terms', [TermsController::class, 'index']);
        Route::post('/terms', [TermsController::class, 'store']);
        Route::get('/terms/suggest', [TermsController::class, 'suggest']);
        Route::delete('/terms/{term}', [TermsController::class, 'destroy']);

        // Akun (§B6).
        Route::get('/accounts', [AccountsController::class, 'index']);
        Route::post('/accounts/{user}/action', [AccountsController::class, 'action']);

        // Metrik kesehatan komunitas (§B2).
        Route::get('/metrics', [MetricsController::class, 'index']);

        // Pendaftar waitlist landing page.
        Route::get('/subscribers', [SubscriberController::class, 'index']);
    });
