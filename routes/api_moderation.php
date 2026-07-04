<?php

use App\Http\Controllers\Console\AccountsController;
use App\Http\Controllers\Console\MetricsController;
use App\Http\Controllers\Console\QueueController;
use App\Http\Controllers\Console\ReportsController;
use App\Http\Controllers\Console\TermsController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
| MODERATION (§A5/§A6) — di /api/v1, auth JWT.
| - POST /reports: terbuka untuk semua pengguna terautentikasi (§06).
| - /mod/*: gerbang admin — token JWT ber-scope 'mod' (hanya pasca-2FA) + role admin.
*/

// Pelaporan pengguna.
Route::middleware('auth:api')->post('/reports', [ReportController::class, 'store']);

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
    });
