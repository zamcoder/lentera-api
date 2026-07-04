<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\ReactRequest;
use App\Http\Requests\Community\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostHide;
use App\Models\Reaction;
use App\Services\Moderation\ModerationPipeline;
use App\Support\Pseudonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PostController (v1, komunitas §7) — Dinding Syukur & kiriman. Bidang B plaintext,
 * dimoderasi (pending → approved/held). Reaksi tanpa komentar.
 */
class PostController extends Controller
{
    private const EMOJIS = ['🌙', '🌿', '🌅', '☁️', '🌫️', '🍂', '🌊', '🌇', '🌸', '🕊️'];
    private const PALS = ['mint', 'peach', 'lav'];

    public function __construct(private readonly ModerationPipeline $pipeline)
    {
    }

    /** GET /api/v1/community/feed?cursor=&circle_id= — feed publik approved. */
    public function feed(Request $request): JsonResponse
    {
        $uid = auth('api')->id();

        $page = Post::query()
            ->published()
            ->where('surface', '!=', Post::SURFACE_STRENGTH)  // "kirim kekuatan" tak disiarkan
            ->when($request->filled('circle_id'), fn ($q) => $q->where('circle_id', $request->string('circle_id')))
            ->whereNotIn('id', PostHide::where('user_id', $uid)->select('post_id'))
            ->with($this->feedWith($uid))
            ->withCount($this->reactionCounts())
            ->orderByDesc('published_at')->orderByDesc('id')
            ->cursorPaginate((int) $request->integer('limit', 20));

        return response()->json([
            'data' => PostResource::collection($page->items()),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /** POST /api/v1/community/posts — kirim (→ pending, masuk moderasi). */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth('api')->user();

        if (in_array($user->status, ['blocked', 'muted'], true)) {
            return response()->json(['message' => 'Akun sedang dibatasi untuk mengirim.'], 403);
        }

        $anon = (bool) ($data['anon'] ?? true);
        $post = Post::create([
            'author_id' => $user->id,
            'circle_id' => $data['circle_id'] ?? null,
            'prompt_id' => $data['prompt_id'] ?? null,
            'surface' => $data['surface'] ?? Post::SURFACE_GRATITUDE,
            'body' => $data['text'],
            'anon' => $anon,
            'pseudonym' => $anon ? Pseudonym::random() : $user->handle,
            'avatar' => $anon ? self::EMOJIS[array_rand(self::EMOJIS)] : mb_substr($user->handle, 0, 1),
            'avatar_pal' => self::PALS[array_rand(self::PALS)],
            'strength' => ($data['surface'] ?? null) === Post::SURFACE_STRENGTH,
            'status' => Post::STATUS_PENDING,
        ]);

        $result = $this->pipeline->moderateSync($post->fresh());
        $post = $this->hydrate($post->fresh(), $user->id);

        return response()->json([
            'post' => new PostResource($post),
            'moderation' => [
                'status' => $result['status'],
                'queued' => $result['queued'],
                'self_harm' => $result['self_harm'],
                'safe_space' => $result['safe_space'] ?? null,
            ],
        ], 201);
    }

    /** GET /api/v1/community/posts/{post} — detail + status moderasi. */
    public function show(string $post): PostResource
    {
        $uid = auth('api')->id();
        $model = Post::query()
            ->where(fn ($q) => $q->where('status', Post::STATUS_APPROVED)->orWhere('author_id', $uid))
            ->findOrFail($post);

        return new PostResource($this->hydrate($model, $uid));
    }

    /** POST /api/v1/community/posts/{post}/react — reaksi hangat (tanpa komentar). */
    public function react(ReactRequest $request, string $post): JsonResponse
    {
        $model = Post::where('status', Post::STATUS_APPROVED)->findOrFail($post);

        Reaction::firstOrCreate([
            'post_id' => $model->id,
            'user_id' => auth('api')->id(),
            'kind' => $request->validated()['kind'],
        ]);

        return response()->json($this->reactionState($model->id, auth('api')->id()));
    }

    /** DELETE /api/v1/community/posts/{post}/react — batal reaksi. */
    public function unreact(ReactRequest $request, string $post): JsonResponse
    {
        Reaction::where('post_id', $post)
            ->where('user_id', auth('api')->id())
            ->where('kind', $request->validated()['kind'])
            ->delete();

        return response()->json($this->reactionState($post, auth('api')->id()));
    }

    /** POST /api/v1/community/posts/{post}/hide — sembunyikan dari feed-ku. */
    public function hide(string $post): JsonResponse
    {
        Post::findOrFail($post);
        PostHide::firstOrCreate(['user_id' => auth('api')->id(), 'post_id' => $post]);

        return response()->json(['hidden' => true]);
    }

    // ---- helpers ----

    private function reactionCounts(): array
    {
        return [
            'reactions as peluk' => fn ($q) => $q->where('kind', Reaction::KIND_PELUK),
            'reactions as kekuatan' => fn ($q) => $q->where('kind', Reaction::KIND_KEKUATAN),
            'reactions as paham' => fn ($q) => $q->where('kind', Reaction::KIND_PAHAM),
        ];
    }

    private function feedWith(string $uid): array
    {
        return [
            'author:id,handle',
            'circle:id,theme',
            'reactions' => fn ($q) => $q->where('user_id', $uid),  // hanya reaksi milikku
        ];
    }

    private function hydrate(Post $post, string $uid): Post
    {
        return $post->load($this->feedWith($uid))->loadCount($this->reactionCounts());
    }

    private function reactionState(string $postId, string $uid): array
    {
        $counts = Reaction::where('post_id', $postId)
            ->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind');
        $mine = Reaction::where('post_id', $postId)->where('user_id', $uid)->pluck('kind');

        return [
            'reactions' => [
                'peluk' => (int) ($counts[Reaction::KIND_PELUK] ?? 0),
                'kekuatan' => (int) ($counts[Reaction::KIND_KEKUATAN] ?? 0),
                'paham' => (int) ($counts[Reaction::KIND_PAHAM] ?? 0),
            ],
            'my_reactions' => $mine->values(),
        ];
    }
}
