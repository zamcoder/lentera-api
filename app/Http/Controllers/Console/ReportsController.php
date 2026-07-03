<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Services\Moderation\ModeratorActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReportsController — layar Laporan konsol (§B4). Konten dilaporkan + alasan
 * pelapor + hitungan; aksi Biarkan tampil / Sembunyikan / Hapus & tindak akun.
 */
class ReportsController extends Controller
{
    public function __construct(private readonly ModeratorActions $actions)
    {
    }

    /** GET /mod/reports — kartu konten dilaporkan (dikelompokkan per kiriman). */
    public function index(Request $request): JsonResponse
    {
        // Kiriman dengan laporan terbuka, beserta jumlah & alasan.
        $page = Post::query()
            ->whereHas('reports', fn ($q) => $q->where('status', 'open'))
            ->with(['author:id,handle'])
            ->withCount(['reports' => fn ($q) => $q->where('status', 'open')])
            ->orderByDesc(
                Report::selectRaw('max(created_at)')->whereColumn('reports.post_id', 'posts.id')->where('status', 'open')
            )
            ->cursorPaginate((int) $request->integer('limit', 15));

        $items = collect($page->items())->map(function (Post $p) {
            $reasons = Report::where('post_id', $p->id)->where('status', 'open')
                ->get(['reason', 'note', 'created_at']);

            return [
                'id' => $p->id,
                'surface' => $p->surface,
                'body' => $p->body,
                'anon' => (bool) $p->anon,
                'author' => $p->anon ? $p->pseudonym : $p->author?->handle,
                'author_id' => $p->author_id,
                'status' => $p->status,
                'self_harm' => (bool) $p->self_harm,
                'reports_count' => $p->reports_count,
                'reasons' => $reasons,
            ];
        });

        return response()->json([
            'data' => $items->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'open_count' => Report::where('status', 'open')->count(),
        ]);
    }

    /**
     * POST /mod/reports/action — tindak konten dilaporkan.
     * decision: keep (biarkan tampil) | hide (sembunyikan) | remove (hapus & tindak akun)
     */
    public function action(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => ['required', 'uuid', 'exists:posts,id'],
            'decision' => ['required', 'in:keep,hide,remove'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $post = Post::findOrFail($data['post_id']);
        $moderatorId = $request->user()->id;

        match ($data['decision']) {
            'keep' => $this->actions->apply($post, 'approve', $moderatorId, $data['reason'] ?? 'Laporan ditinjau: aman.'),
            'hide' => $this->actions->apply($post, 'hold', $moderatorId, $data['reason'] ?? 'Disembunyikan pasca-laporan.'),
            'remove' => $this->actions->apply($post, 'reject', $moderatorId, $data['reason'] ?? 'Dihapus pasca-laporan.'),
        };

        return response()->json([
            'message' => 'Laporan ditangani.',
            'open_count' => Report::where('status', 'open')->count(),
        ]);
    }
}
