<?php

use App\Http\Controllers\Api\V1\PersonController;
use Illuminate\Support\Facades\Route;

/*
| ORANG (People, §3) — jurnal E2E. Field terenkripsi; server buta.
| Butuh JWT (auth:api). Di-mount di bawah /api/v1.
*/
// sync.on: tulis (POST/PUT) ditolak 409 bila sinkron awan dimatikan; baca & hapus boleh.
Route::middleware(['auth:api', 'sync.on'])->group(function () {
    Route::get('/people', [PersonController::class, 'index']);
    Route::post('/people', [PersonController::class, 'store']);
    Route::put('/people/{person}', [PersonController::class, 'update']);
    Route::delete('/people/{person}', [PersonController::class, 'destroy']);
});
