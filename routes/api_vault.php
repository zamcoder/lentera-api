<?php

use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\VaultController;
use Illuminate\Support\Facades\Route;

/*
| SYNC / VAULT (§2) — cadangan jurnal terenkripsi. Server buta terhadap isi.
| Semua butuh JWT (auth:api). Di-mount di bawah /api/v1.
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/vault/status', [VaultController::class, 'status']);
    Route::put('/vault/backup', [VaultController::class, 'backup']);
    Route::delete('/vault/backup', [VaultController::class, 'destroy']);
    Route::get('/vault/restore', [VaultController::class, 'restore']);

    // Toggle sinkron awan (§2).
    Route::put('/settings/sync', [SettingsController::class, 'sync']);
});
