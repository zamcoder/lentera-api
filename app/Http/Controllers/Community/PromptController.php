<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\DailyPrompt;
use Illuminate\Http\JsonResponse;

/**
 * PromptController — prompt bersama harian (§03/§A4, GET /prompt/today).
 */
class PromptController extends Controller
{
    public function today(): JsonResponse
    {
        $prompt = DailyPrompt::whereDate('prompt_date', now()->toDateString())->first();

        if (! $prompt) {
            return response()->json([
                'prompt' => null,
                'message' => 'Belum ada prompt untuk hari ini.',
            ]);
        }

        return response()->json([
            'prompt' => [
                'id' => $prompt->id,
                'date' => $prompt->prompt_date->toDateString(),
                'body' => $prompt->body,
            ],
        ]);
    }
}
