<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReportController — pelaporan pengguna atas kiriman (§06, POST /reports).
 * Laporan masuk ke konsol admin (§07). Terbuka untuk semua pengguna terautentikasi.
 */
class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => ['required', 'uuid', 'exists:posts,id'],
            'reason' => ['required', 'in:spam,harassment,self_harm,hate,other'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $report = Report::create([
            'post_id' => $data['post_id'],
            'reporter_id' => $request->user()->id,
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
            'status' => 'open',
        ]);

        // Laporan self-harm → tandai kiriman untuk penanganan khusus & tahan.
        if ($data['reason'] === 'self_harm') {
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
