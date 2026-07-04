<?php

use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SafetyController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

/*
| PENGATURAN & NOTIFIKASI (§12) + KESELAMATAN (§11) — butuh JWT. /api/v1.
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);
    Route::put('/settings/sync', [SettingsController::class, 'sync']);
    Route::put('/settings/reminder', [SettingsController::class, 'reminder']);

    Route::post('/notifications/token', [NotificationController::class, 'registerToken']);

    Route::get('/safety/hotlines', [SafetyController::class, 'hotlines']);
});
