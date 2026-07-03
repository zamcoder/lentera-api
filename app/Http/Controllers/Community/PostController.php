<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Reaction;
use App\Services\Moderation\ModerationPipeline;
use App\Support\Pseudonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * PostController — Dinding Syukur & permukaan komunitas (§A4).
 * Feed hanya menampilkan kiriman approved. Tanpa jumlah like/pengikut publik
 * (§03 anti-cemas). Kiriman baru masuk pipa moderasi (§06).
 */
class PostController extends Controller
{
    public function __construct(private readonly ModerationPipeline $pipeline)
    {
    }

    /**
     * GET /feed — feed publik per surface, paginasi cursor.
     * ?surface=gratitude|strength|prompt (default gratitude).
     */
    public function feed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'surface' => ['nullable', 'in:gratitude,strength,prompt'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $surface = $data['surface'] ?? Post::SURFACE_GRATITUDE;

        $page = Post::query()
            ->published()
            ->where('surface', $surface)
            ->orderByDesc('published_at')
            ->cursorPaginate($data['limit'] ?? 20);

        return response()->json([
            'surface' => $surface,
            'data' => collect($page->items())->map(fn (Post $p) => $this->present($p, $request))->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /**
     * POST /posts — kirim ke komunitas. Masuk pipa moderasi.
     * "Kirim kekuatan" (surface=strength) hanya menerima pesan siap-pakai.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'surface' => ['required', 'in:gratitude,strength,prompt,circle'],
            'body' => ['required', 'string', 'max:2000'],
            'anon' => ['boolean'],
            'circle_id' => ['nullable', 'uuid', 'required_if:surface,circle', 'exists:circles,id'],
            'prompt_id' => ['nullable', 'uuid', 'exists:daily_prompts,id'],
        ]);

        $user = $request->user();
        if ($user->status === 'blocked' || $user->status === 'muted') {
            throw ValidationException::withMessages(['body' => ['Akun sedang dibatasi untuk mengirim.']]);
        }

        // "Kirim kekuatan": TANPA teks bebas — wajib salah satu pesan siap-pakai.
        if ($data['surface'] === Post::SURFACE_STRENGTH) {
            $ready = array_map('trim', (array) config('lentera.strength_messages'));
            if (! in_array(trim($data['body']), $ready, true)) {
                throw ValidationException::withMessages([
                    'body' => ['Kirim kekuatan hanya menerima pesan siap-pakai.'],
                ]);
            }
        }

        $anon = (bool) ($data['anon'] ?? true);

        $post = Post::create([
            'author_id' => $user->id,
            'circle_id' => $data['circle_id'] ?? null,
            'prompt_id' => $data['prompt_id'] ?? null,
            'surface' => $data['surface'],
            'body' => $data['body'],
            'anon' => $anon,
            // Nama samaran per-kiriman bila anon (tak tertaut identitas).
            'pseudonym' => $anon ? Pseudonym::random() : $user->handle,
            'status' => Post::STATUS_PENDING,
        ]);

        $result = $this->pipeline->moderateSync($post->fresh());
        $post->refresh();

        return response()->json([
            'post' => $this->present($post, $request),
            'moderation' => [
                'status' => $result['status'],
                'queued' => $result['queued'],
                // Sinyal ke klien untuk menawarkan Ruang Tenang (§06).
                'self_harm' => $result['self_harm'],
                'safe_space' => $result['safe_space'] ?? null,
            ],
        ], 201);
    }

    /**
     * POST /posts/{post}/react — reaksi hangat (peluk/kekuatan/paham).
     * TANPA komentar. Instan. Idempoten per (post,user,kind).
     */
    public function react(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:'.implode(',', Reaction::KINDS)],
        ]);

        abort_unless($post->status === Post::STATUS_APPROVED, 404);

        Reaction::firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'kind' => $data['kind'],
        ]);

        // Tak mengembalikan jumlah (anti-cemas): hanya konfirmasi reaksi diri.
        return response()->json([
            'reacted' => true,
            'kind' => $data['kind'],
        ]);
    }

    /**
     * DELETE /posts/{post}/react?kind= — tarik reaksi.
     */
    public function unreact(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:'.implode(',', Reaction::KINDS)],
        ]);

        Reaction::where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->where('kind', $data['kind'])
            ->delete();

        return response()->json(['reacted' => false, 'kind' => $data['kind']]);
    }

    /**
     * Bentuk kiriman untuk klien. Menghormati anonimitas & tanpa metrik publik.
     */
    private function present(Post $post, Request $request): array
    {
        $myReactions = [];
        if ($user = $request->user()) {
            $myReactions = Reaction::where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->pluck('kind')
                ->all();
        }

        return [
            'id' => $post->id,
            'surface' => $post->surface,
            'body' => $post->body,
            'anon' => (bool) $post->anon,
            'author' => $post->anon ? $post->pseudonym : $post->author?->handle,
            'circle_id' => $post->circle_id,
            'my_reactions' => $myReactions,
            'published_at' => $post->published_at,
        ];
    }
}
