<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * MetricsController — kesehatan komunitas untuk Ringkasan (§A6/§B2).
 *
 * Sengaja menghindari metrik pertumbuhan yang bikin cemas (jumlah pengguna
 * naik, dsb). Fokus ke indikator KEHANGATAN: rasio positif, antrean menunggu,
 * kecepatan moderasi, anggota aktif.
 */
class MetricsController extends Controller
{
    public function index(): JsonResponse
    {
        $approved = Post::where('status', Post::STATUS_APPROVED)->count();
        $rejected = Post::where('status', Post::STATUS_REJECTED)->count();
        $held = Post::whereIn('status', [Post::STATUS_HELD, Post::STATUS_ESCALATED])->count();
        $pending = Post::where('status', Post::STATUS_PENDING)->count();

        $moderated = $approved + $rejected + $held;
        $positiveRatio = $moderated > 0 ? round($approved / $moderated, 3) : 1.0;

        $queueWaiting = $held + $pending;
        $openReports = Report::where('status', 'open')->count();

        // Kecepatan moderasi: rata-rata detik dari kiriman dibuat → tindakan pertama.
        $speedSeconds = DB::table('moderation_actions as m')
            ->join('posts as p', 'p.id', '=', 'm.post_id')
            ->whereRaw("m.created_at >= now() - interval '30 days'")
            ->avg(DB::raw('EXTRACT(EPOCH FROM (m.created_at - p.created_at))'));

        // Anggota aktif 7 hari: pernah mengirim atau bereaksi.
        $activePosters = Post::whereRaw("created_at >= now() - interval '7 days'")->distinct()->count('author_id');
        $activeReactors = Reaction::whereRaw("created_at >= now() - interval '7 days'")->distinct()->count('user_id');
        $activeMembers = max($activePosters, $activeReactors);

        $score = (int) round($positiveRatio * 100);

        return response()->json([
            'health' => [
                'score' => $score,
                'label' => $this->warmthLabel($score),
            ],
            'cards' => [
                'positive_ratio' => $positiveRatio,       // 0..1
                'queue_waiting' => $queueWaiting,
                'moderation_speed_seconds' => $speedSeconds ? (int) round($speedSeconds) : null,
                'active_members' => $activeMembers,
            ],
            'attention' => [
                'queue' => $queueWaiting,
                'reports' => $openReports,
                'self_harm_held' => Post::where('self_harm', true)
                    ->whereIn('status', [Post::STATUS_HELD, Post::STATUS_ESCALATED])->count(),
            ],
        ]);
    }

    private function warmthLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Hangat',
            $score >= 65 => 'Sejuk',
            $score >= 40 => 'Berawan',
            default => 'Perlu perhatian',
        };
    }
}
