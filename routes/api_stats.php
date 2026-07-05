<?php

use App\Http\Controllers\Api\V1\MoodController;
use App\Http\Controllers\Api\V1\ReflectionController;
use App\Http\Controllers\Api\V1\StatsController;
use Illuminate\Support\Facades\Route;

/*
| MOOD, STATISTIK & REKAP (§6) — hanya agregat non-sensitif. Butuh JWT. /api/v1.
| Refleksi harian "Tiga baris malam" = E2E (server buta simpan ciphertext).
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/stats/summary', [StatsController::class, 'summary']);
    Route::get('/stats/mood', [StatsController::class, 'moodMonth']);   // kalender mood 1 bulan
    Route::get('/today', [StatsController::class, 'today']);
    Route::post('/mood', [MoodController::class, 'store']);

    // Refleksi harian E2E (§6).
    Route::get('/reflections', [ReflectionController::class, 'index']);
    Route::get('/reflections/{date}', [ReflectionController::class, 'show']);
    // sync.on: refleksi E2E ditolak 409 bila sinkron awan dimatikan (mood/stats
    // agregat tetap jalan — bukan data E2E).
    Route::put('/reflections/{date}', [ReflectionController::class, 'upsert'])->middleware('sync.on');
});
