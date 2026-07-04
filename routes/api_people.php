<?php

use App\Http\Controllers\Api\V1\PersonController;
use Illuminate\Support\Facades\Route;

/*
| ORANG (People, §3) — jurnal E2E. Field terenkripsi; server buta.
| Butuh JWT (auth:api). Di-mount di bawah /api/v1.
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/people', [PersonController::class, 'index']);
    Route::post('/people', [PersonController::class, 'store']);
    Route::put('/people/{person}', [PersonController::class, 'update']);
    Route::delete('/people/{person}', [PersonController::class, 'destroy']);
});
