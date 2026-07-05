<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCircleRequest;
use App\Http\Resources\CircleResource;
use App\Http\Resources\PostResource;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\Post;
use App\Models\Reaction;
use App\Services\Moderation\BannedWordFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CircleController (v1, §8) — lingkaran kecil bertema: list/detail/create/join/leave + feed.
 */
class CircleController extends Controller
{
    private const PALS = ['mint', 'peach', 'lav'];

    private const MAX_PER_USER = 5;

    /** POST /api/v1/circles — buat lingkaran (langsung publik; filter kata terlarang). */
    public function store(StoreCircleRequest $request, BannedWordFilter $filter): JsonResponse
    {
        $uid = auth('api')->id();
        $data = $request->validated();

        if (Circle::where('created_by', $uid)->count() >= self::MAX_PER_USER) {
            return response()->json(['message' => 'Kamu sudah mencapai batas '.self::MAX_PER_USER.' lingkaran.'], 422);
        }

        // Filter kata terlarang di nama + deskripsi (tolak bila melanggar).
        if ($filter->scan(trim($data['name'].' '.($data['description'] ?? '')))['blocked']) {
            return response()->json([
                'message' => 'Nama atau deskripsi mengandung kata yang tidak diperbolehkan.',
                'errors' => ['name' => ['Mengandung kata yang tidak diperbolehkan.']],
            ], 422);
        }

        $circle = DB::transaction(function () use ($data, $uid) {
            $circle = Circle::create([
                'slug' => $this->uniqueSlug($data['name']),
                'theme' => $data['name'],
                'emoji' => ($data['emoji'] ?? '') !== '' ? $data['emoji'] : '🌱',
                'pal' => self::PALS[array_rand(self::PALS)],
                'description' => $data['description'] ?? null,
                'created_by' => $uid,
            ]);
            CircleMember::create(['circle_id' => $circle->id, 'user_id' => $uid]);

            return $circle;
        });

        $circle->loadCount($this->counts());

        // Flat (bukan {data:...}) agar cocok bentuk item GET /circles.
        return response()->json((new CircleResource($circle))->resolve(), 201);
    }

    /** Slug unik dari nama (nama boleh duplikat, slug tetap unik). */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'lingkaran';
        $slug = $base;
        while (Circle::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    /** GET /api/v1/circles — daftar lingkaran (+joined, member_count). */
    public function index(): AnonymousResourceCollection
    {
        $circles = Circle::withCount($this->counts())
            ->orderByDesc('members_count')->orderBy('theme')->get();

        return CircleResource::collection($circles);
    }

    /** GET /api/v1/circles/{circle} — detail. */
    public function show(Circle $circle): CircleResource
    {
        $circle->loadCount($this->counts());

        return new CircleResource($circle);
    }

    /** POST /api/v1/circles/{circle}/join — gabung (idempoten). */
    public function join(Circle $circle): JsonResponse
    {
        CircleMember::firstOrCreate([
            'circle_id' => $circle->id,
            'user_id' => auth('api')->id(),
        ]);

        return response()->json(['joined' => true, 'member_count' => $circle->members()->count()]);
    }

    /** DELETE /api/v1/circles/{circle}/join — keluar. */
    public function leave(Circle $circle): JsonResponse
    {
        CircleMember::where('circle_id', $circle->id)
            ->where('user_id', auth('api')->id())->delete();

        return response()->json(['joined' => false, 'member_count' => $circle->members()->count()]);
    }

    /** GET /api/v1/circles/{circle}/feed — feed kiriman approved di lingkaran. */
    public function feed(Request $request, Circle $circle): JsonResponse
    {
        $uid = auth('api')->id();
        $circle->loadCount($this->counts());

        $page = Post::query()
            ->published()
            ->where('circle_id', $circle->id)
            ->with(['author:id,handle', 'circle:id,theme', 'reactions' => fn ($q) => $q->where('user_id', $uid)])
            ->withCount([
                'reactions as peluk' => fn ($q) => $q->where('kind', Reaction::KIND_PELUK),
                'reactions as kekuatan' => fn ($q) => $q->where('kind', Reaction::KIND_KEKUATAN),
                'reactions as paham' => fn ($q) => $q->where('kind', Reaction::KIND_PAHAM),
            ])
            ->orderByDesc('published_at')->orderByDesc('id')
            ->cursorPaginate((int) $request->integer('limit', 20));

        return response()->json([
            'circle' => new CircleResource($circle),
            'data' => PostResource::collection($page->items()),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /**
     * withCount: `members_count` = jumlah anggota ASLI (live), `joined_count` =
     * apakah user saat ini ikut. member_count yang tampil selalu dari sini.
     */
    private function counts(): array
    {
        $uid = auth('api')->id();

        return [
            'members as members_count',
            'members as joined_count' => fn ($q) => $q->where('user_id', $uid),
        ];
    }
}
