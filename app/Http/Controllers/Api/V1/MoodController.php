<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stats\SetMoodRequest;
use App\Models\Mood;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * MoodController (v1) — set mood harian (§6). Satu mood per hari (upsert).
 */
class MoodController extends Controller
{
    /** POST /api/v1/mood — set mood (0..4) untuk suatu tanggal (default hari ini). */
    public function store(SetMoodRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Default tanggal = hari lokal user (WIB), agar konsisten dgn /today & chart.
        $date = isset($data['date'])
            ? Carbon::parse($data['date'])->toDateString()
            : Carbon::now(config('lentera.timezone'))->toDateString();

        $mood = Mood::updateOrCreate(
            ['user_id' => auth('api')->id(), 'mood_date' => $date],
            ['mood_index' => $data['mood_index']],
        );

        return response()->json([
            'date' => $date,
            'mood_index' => $mood->mood_index,
        ]);
    }
}
