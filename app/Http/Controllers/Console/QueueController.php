<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Moderation\ModeratorActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * QueueController — antrean moderasi konsol (§A5/§B3, GET /mod/queue, POST /mod/action).
 * Menampilkan kiriman tertahan dengan alasan AI. Isyarat menyakiti diri ditandai
 * agar UI memakai perlakuan lembut (bukan blokir dingin).
 */
class QueueController extends Controller
{
    public function __construct(private readonly ModeratorActions $actions)
    {
    }

    /** GET /mod/queue — kiriman menunggu tinjauan (held/escalated/pending). */
    public function index(Request $request): JsonResponse
    {
        $statuses = [Post::STATUS_HELD, Post::STATUS_ESCALATED, Post::STATUS_PENDING];

        $page = Post::query()
            ->whereIn('status', $statuses)
            ->with('author:id,handle')
            ->withCount(['reports' => fn ($q) => $q->where('status', 'open')])
            ->orderByDesc('created_at')
            ->cursorPaginate((int) $request->integer('limit', 15));

        return response()->json([
            'data' => collect($page->items())->map(fn (Post $p) => $this->present($p))->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'pending_count' => Post::whereIn('status', $statuses)->count(),
        ]);
    }

    /** POST /mod/action — terapkan keputusan moderator ke kiriman. */
    public function action(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => ['required', 'uuid', 'exists:posts,id'],
            'action' => ['required', 'in:'.implode(',', ModeratorActions::ACTIONS)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $post = Post::findOrFail($data['post_id']);
        $post = $this->actions->apply($post, $data['action'], $request->user()->id, $data['reason'] ?? null);

        $remaining = Post::whereIn('status', [Post::STATUS_HELD, Post::STATUS_ESCALATED, Post::STATUS_PENDING])->count();

        return response()->json([
            'message' => 'Tindakan tercatat.',
            'post' => ['id' => $post->id, 'status' => $post->status],
            'pending_count' => $remaining, // untuk memperbarui badge sidebar
        ]);
    }

    private function present(Post $post): array
    {
        return [
            'id' => $post->id,
            'surface' => $post->surface,
            'body' => $post->body,
            'anon' => (bool) $post->anon,
            'author' => $post->anon ? $post->pseudonym : $post->author?->handle,
            'status' => $post->status,
            'mod_source' => $post->mod_source,
            'mod_reason' => $post->mod_reason,   // alasan AI/regex untuk konsol
            'self_harm' => (bool) $post->self_harm,
            'reports_count' => $post->reports_count,
            'created_at' => $post->created_at,
        ];
    }
}
