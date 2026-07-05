<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreAnswerRequest;
use App\Http\Resources\PromptAnswerResource;
use App\Models\DailyPrompt;
use App\Models\PromptAnswer;
use App\Services\Moderation\ModerationPipeline;
use App\Support\Pseudonym;
use App\Support\SoftAvatar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PromptController (v1, §9) — Prompt bersama harian + jawaban (moderated).
 */
class PromptController extends Controller
{
    public function __construct(private readonly ModerationPipeline $pipeline)
    {
    }

    /** GET /api/v1/prompts/today — pertanyaan hari ini + jumlah berbagi. */
    public function today(): JsonResponse
    {
        $prompt = $this->todayPrompt();

        return response()->json([
            'prompt' => [
                'id' => $prompt->id,
                'date' => $prompt->prompt_date->toDateString(),
                'question' => $prompt->body,
                'share_count' => PromptAnswer::where('prompt_id', $prompt->id)->published()->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/prompts/today/answers?cursor= — jawaban yang tampil (approved).
     * Jawaban dimoderasi SINKRON saat dikirim → status akhir langsung diketahui
     * lewat respons POST (approved tampil di sini; held→safe_space; rejected).
     */
    public function answers(Request $request): JsonResponse
    {
        $prompt = $this->todayPrompt();

        $page = PromptAnswer::where('prompt_id', $prompt->id)
            ->published()
            ->with('author:id,handle')
            ->orderByDesc('published_at')->orderByDesc('id')
            ->cursorPaginate((int) $request->integer('limit', 20));

        return response()->json([
            'data' => PromptAnswerResource::collection($page->items()),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /** POST /api/v1/prompts/today/answers — bagikan jawaban (kena moderasi). */
    public function storeAnswer(StoreAnswerRequest $request): JsonResponse
    {
        $prompt = $this->todayPrompt();
        abort_unless($prompt, 404, 'Belum ada prompt hari ini.');

        $data = $request->validated();
        $user = auth('api')->user();
        $anon = (bool) ($data['anon'] ?? true);

        $mod = $this->pipeline->moderateText($data['text']);

        $answer = PromptAnswer::create([
            'prompt_id' => $prompt->id,
            'author_id' => $user->id,
            'text' => $mod['text'],
            'anon' => $anon,
            'pseudonym' => $anon ? Pseudonym::random() : $user->handle,
            'avatar' => $anon ? SoftAvatar::emoji() : mb_substr($user->handle, 0, 1),
            'avatar_pal' => SoftAvatar::pal(),
            'status' => $mod['status'],
            'mod_source' => 'auto',
            'mod_reason' => $mod['reason'],
            'self_harm' => $mod['self_harm'],
            'published_at' => $mod['status'] === PromptAnswer::STATUS_APPROVED ? now() : null,
        ]);

        return response()->json([
            'answer' => new PromptAnswerResource($answer->load('author:id,handle')),
            'moderation' => [
                'status' => $mod['status'],
                'self_harm' => $mod['self_harm'],
                'safe_space' => $mod['safe_space'] ?? null,
            ],
        ], 201);
    }

    private function todayPrompt(): DailyPrompt
    {
        return DailyPrompt::todayFor(config('lentera.timezone'));
    }
}
