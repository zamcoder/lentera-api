<?php

use App\Http\Controllers\Api\V1\MoodController;
use App\Http\Controllers\Api\V1\StatsController;
use Illuminate\Support\Facades\Route;

/*
| MOOD, STATISTIK & REKAP (§6) — hanya agregat non-sensitif. Butuh JWT. /api/v1.
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/stats/summary', [StatsController::class, 'summary']);
    Route::get('/today', [StatsController::class, 'today']);
    Route::post('/mood', [MoodController::class, 'store']);
});
