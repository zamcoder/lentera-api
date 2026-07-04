<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreReportRequest;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

/**
 * ReportController (v1, §10) — pelaporan pengguna atas kiriman. Masuk antrean
 * konsol. Laporan "isyarat menyakiti diri" → tahan kiriman untuk penanganan khusus.
 */
class ReportController extends Controller
{
    /** POST /api/v1/reports — laporkan kiriman. */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $report = Report::create([
            'post_id' => $data['post_id'],
            'reporter_id' => auth('api')->id(),
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
            'status' => 'open',
        ]);

        // Laporan krisis → tahan dari publik + tandai penanganan khusus (bukan blokir dingin).
        if ($data['reason'] === config('lentera.self_harm_reason')) {
            $post = Post::find($data['post_id']);
            if ($post && $post->status === Post::STATUS_APPROVED) {
                $post->forceFill([
                    'status' => Post::STATUS_HELD,
                    'self_harm' => true,
                    'mod_reason' => 'Dilaporkan: isyarat menyakiti diri.',
                ])->save();
            }
        }

        return response()->json([
            'message' => 'Terima kasih. Laporanmu membantu menjaga ruang ini tetap hangat.',
            'report_id' => $report->id,
        ], 201);
    }
}
