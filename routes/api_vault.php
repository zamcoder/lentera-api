<?php

use App\Http\Controllers\VaultController;
use Illuminate\Support\Facades\Route;

/*
| SYNC / VAULT (§A3) — cadangan jurnal terenkripsi. Server buta terhadap isi.
| Semua butuh token app.
*/
Route::middleware('auth:sanctum')->prefix('vault')->group(function () {
    Route::put('/backup', [VaultController::class, 'backup']);
    Route::delete('/backup', [VaultController::class, 'destroy']);
    Route::get('/restore', [VaultController::class, 'restore']);
    Route::get('/status', [VaultController::class, 'status']);
});
