<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Models\BannedTerm;
use Illuminate\Http\JsonResponse;

/**
 * ModerationController (v1, §10) — sinkron daftar moderasi ke klien agar deteksi
 * di app IDENTIK dengan server & konsol (banned words + isyarat krisis).
 */
class ModerationController extends Controller
{
    /** GET /api/v1/moderation/banned-terms — daftar kata terlarang + isyarat krisis. */
    public function bannedTerms(): JsonResponse
    {
        return response()->json([
            'banned_terms' => BannedTerm::orderBy('pattern')->pluck('pattern')->values(),
            'crisis_signals' => array_values((array) config('lentera.self_harm_signals')),
        ]);
    }
}
