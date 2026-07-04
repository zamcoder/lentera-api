<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\SendStrengthRequest;
use App\Models\Post;
use App\Models\StrengthSend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StrengthController (v1, §9) — "Kirim kekuatan". Antrean orang yang sedang
 * berat (kiriman surface=strength) + kirim pesan template INSTAN (tanpa pra-tayang).
 */
class StrengthController extends Controller
{
    /** GET /api/v1/strength/queue — orang yang butuh dukungan. */
    public function queue(Request $request): JsonResponse
    {
        $uid = auth('api')->id();

        $page = Post::query()
            ->published()
            ->where('surface', Post::SURFACE_STRENGTH)
            ->where('author_id', '!=', $uid)                       // bukan kiriman sendiri
            ->whereNotIn('id', StrengthSend::where('sender_id', $uid)->select('post_id')) // yang belum kukirimi
            ->with('author:id,handle')
            ->orderByDesc('published_at')->orderByDesc('id')
            ->cursorPaginate((int) $request->integer('limit', 20));

        $items = collect($page->items())->map(fn (Post $p) => [
            'id' => $p->id,
            'author' => $p->anon ? $p->pseudonym : $p->author?->handle,
            'avatar' => $p->avatar,
            'avatar_pal' => $p->avatar_pal,
            'time' => optional($p->published_at)->diffForHumans(),
            'text' => $p->body,
        ]);

        return response()->json([
            'data' => $items->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /** POST /api/v1/strength/{post}/send — kirim pesan kekuatan siap-pakai (instan). */
    public function send(SendStrengthRequest $request, string $post): JsonResponse
    {
        $model = Post::where('surface', Post::SURFACE_STRENGTH)
            ->where('status', Post::STATUS_APPROVED)
            ->findOrFail($post);

        $uid = auth('api')->id();
        abort_if($model->author_id === $uid, 422, 'Tidak bisa mengirim kekuatan ke kiriman sendiri.');

        // Instan, tanpa pra-tayang — satu kiriman per (pengirim, post).
        StrengthSend::firstOrCreate(
            ['sender_id' => $uid, 'post_id' => $model->id],
            ['message' => $request->validated()['message']],
        );

        return response()->json([
            'sent' => true,
            'message' => 'Kekuatan terkirim 💛',
        ], 201);
    }
}
