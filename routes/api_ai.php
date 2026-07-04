<?php

use App\Http\Controllers\Api\V1\AiSummaryController;
use Illuminate\Support\Facades\Route;

/*
| Ringkasan AI (gated consent, §opsional) — /api/v1, JWT.
| Menerima plaintext (menembus E2E atas izin user), transien & tak disimpan.
| throttle untuk mengendalikan biaya token LLM.
*/
Route::middleware(['auth:api', 'throttle:30,1'])->prefix('ai')->group(function () {
    Route::post('/summarize/person', [AiSummaryController::class, 'person']);
    Route::post('/summarize/day', [AiSummaryController::class, 'day']);
});
