<?php

use App\Http\Controllers\Api\V1\VaultController;
use Illuminate\Support\Facades\Route;

/*
| SYNC / VAULT (§2) — cadangan jurnal terenkripsi. Server buta terhadap isi.
| Semua butuh JWT (auth:api). Di-mount di bawah /api/v1.
| (Toggle /settings/sync ada di api_settings.php)
*/
// sync.on: PUT /vault/backup ditolak 409 bila sinkron awan dimatikan user.
// GET (status/restore) & DELETE (hak lupa) tetap boleh (lihat EnsureSyncEnabled).
Route::middleware(['auth:api', 'sync.on'])->group(function () {
    Route::get('/vault/status', [VaultController::class, 'status']);
    Route::put('/vault/backup', [VaultController::class, 'backup']);
    Route::delete('/vault/backup', [VaultController::class, 'destroy']);
    Route::get('/vault/restore', [VaultController::class, 'restore']);
});
