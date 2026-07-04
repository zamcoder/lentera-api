<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\SummarizeDayRequest;
use App\Http\Requests\Ai\SummarizePersonRequest;
use App\Services\Ai\AiSummarizer;
use Illuminate\Http\JsonResponse;

/**
 * AiSummaryController (v1) — "Ringkasan AI" (gated consent, §opsional).
 * Menerima PLAINTEXT (menembus E2E atas izin eksplisit user di app). Konten
 * TIDAK disimpan/di-log; hanya diteruskan ke LLM. Balikan {summary} atau
 * {summary: null} bila kosong/gagal (app fallback ke template lokal).
 */
class AiSummaryController extends Controller
{
    public function __construct(private readonly AiSummarizer $ai)
    {
    }

    /** POST /api/v1/ai/summarize/person */
    public function person(SummarizePersonRequest $request): JsonResponse
    {
        return response()->json(['summary' => $this->ai->person($request->validated())]);
    }

    /** POST /api/v1/ai/summarize/day */
    public function day(SummarizeDayRequest $request): JsonResponse
    {
        return response()->json(['summary' => $this->ai->day($request->validated())]);
    }
}
