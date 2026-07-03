<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CircleController — lingkaran kecil bertema (§03/§A4): list/detail/join + feed.
 */
class CircleController extends Controller
{
    /** GET /circles — daftar lingkaran. */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $joined = CircleMember::where('user_id', $userId)->pluck('circle_id')->all();

        $circles = Circle::orderBy('theme')->get()->map(fn (Circle $c) => [
            'id' => $c->id,
            'slug' => $c->slug,
            'theme' => $c->theme,
            'description' => $c->description,
            'member_count' => $c->member_count,
            'joined' => in_array($c->id, $joined, true),
        ]);

        return response()->json(['data' => $circles]);
    }

    /** GET /circles/{circle} — detail satu lingkaran. */
    public function show(Request $request, Circle $circle): JsonResponse
    {
        $joined = CircleMember::where('circle_id', $circle->id)
            ->where('user_id', $request->user()->id)->exists();

        return response()->json([
            'id' => $circle->id,
            'slug' => $circle->slug,
            'theme' => $circle->theme,
            'description' => $circle->description,
            'member_count' => $circle->member_count,
            'joined' => $joined,
        ]);
    }

    /** POST /circles/{circle}/join — gabung lingkaran (idempoten). */
    public function join(Request $request, Circle $circle): JsonResponse
    {
        $member = CircleMember::firstOrCreate([
            'circle_id' => $circle->id,
            'user_id' => $request->user()->id,
        ]);

        if ($member->wasRecentlyCreated) {
            $circle->increment('member_count');
        }

        return response()->json(['joined' => true, 'member_count' => $circle->fresh()->member_count]);
    }

    /** DELETE /circles/{circle}/leave — keluar lingkaran. */
    public function leave(Request $request, Circle $circle): JsonResponse
    {
        $deleted = CircleMember::where('circle_id', $circle->id)
            ->where('user_id', $request->user()->id)->delete();

        if ($deleted) {
            $circle->decrement('member_count');
        }

        return response()->json(['joined' => false, 'member_count' => $circle->fresh()->member_count]);
    }

    /** GET /circles/{circle}/feed — feed kiriman approved di lingkaran. */
    public function feed(Request $request, Circle $circle): JsonResponse
    {
        $page = Post::query()
            ->published()
            ->where('circle_id', $circle->id)
            ->orderByDesc('published_at')
            ->cursorPaginate((int) $request->integer('limit', 20));

        $userId = $request->user()->id;

        $items = collect($page->items())->map(function (Post $p) use ($userId) {
            return [
                'id' => $p->id,
                'body' => $p->body,
                'anon' => (bool) $p->anon,
                'author' => $p->anon ? $p->pseudonym : $p->author?->handle,
                'my_reactions' => Reaction::where('post_id', $p->id)->where('user_id', $userId)->pluck('kind')->all(),
                'published_at' => $p->published_at,
            ];
        });

        return response()->json([
            'circle' => ['id' => $circle->id, 'theme' => $circle->theme],
            'data' => $items->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }
}
