<?php

use App\Http\Controllers\Api\V1\InteractionController;
use App\Http\Controllers\Api\V1\MediaController;
use Illuminate\Support\Facades\Route;

/*
| MOMEN/INTERAKSI (§4) + MEDIA (§5) — jurnal E2E. Butuh JWT. Di /api/v1.
*/
// sync.on: tulis (POST/PUT) ditolak 409 bila sinkron awan dimatikan; baca & hapus boleh.
Route::middleware(['auth:api', 'sync.on'])->group(function () {
    Route::get('/interactions', [InteractionController::class, 'index']);
    Route::post('/interactions', [InteractionController::class, 'store']);
    Route::put('/interactions/{interaction}', [InteractionController::class, 'update']);
    Route::delete('/interactions/{interaction}', [InteractionController::class, 'destroy']);

    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{media}', [MediaController::class, 'show']);
});
