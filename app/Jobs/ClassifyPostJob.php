<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\Moderation\GeminiModerator;
use App\Services\Moderation\ModerationPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * ClassifyPostJob — Lapis 2 moderasi asinkron (§06/§A5). Memanggil Gemini untuk
 * menilai nada & niat, lalu menetapkan status akhir:
 *   ok        → approved (tampil)
 *   sedang    → held (masuk antrean konsol)
 *   tinggi    → rejected
 *   self_harm → held + penanganan khusus (tak pernah blokir dingin)
 */
class ClassifyPostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public string $postId)
    {
    }

    public function handle(GeminiModerator $gemini, ModerationPipeline $pipeline): void
    {
        $post = Post::find($this->postId);
        if (! $post) {
            return;
        }

        // Hanya proses kiriman yang MASIH menunggu. Bila Lapis 1 sudah
        // memfinalkan (rejected, atau held karena self-harm), jangan di-approve
        // ulang — cukup lampirkan konteks AI untuk moderator.
        if ($post->status !== Post::STATUS_PENDING) {
            $ctx = $gemini->classify($post->body);
            $pipeline->record($post, 'classify', 'ai', $ctx['reason'], null, [
                'label' => $ctx['label'],
                'score' => $ctx['score'],
                'categories' => $ctx['categories'],
                'context_only' => true,
            ]);

            return;
        }

        $result = $gemini->classify($post->body);
        $hold = (float) config('lentera.moderation.hold_threshold');
        $reject = (float) config('lentera.moderation.reject_threshold');
        $score = $result['score'];

        $meta = [
            'label' => $result['label'],
            'score' => $score,
            'categories' => $result['categories'],
        ];

        // Isyarat menyakiti diri dari AI → held + penanganan khusus.
        if ($result['self_harm']) {
            $post->forceFill([
                'status' => Post::STATUS_HELD,
                'self_harm' => true,
                'mod_source' => 'ai',
                'mod_reason' => $result['reason'] ?: 'AI: isyarat menyakiti diri.',
            ])->save();
            $pipeline->record($post, 'hold', 'ai', $post->mod_reason, null, $meta + ['self_harm' => true]);

            return;
        }

        if ($score >= $reject) {
            $post->forceFill([
                'status' => Post::STATUS_REJECTED,
                'mod_source' => 'ai',
                'mod_reason' => $result['reason'] ?: 'AI: pelanggaran berat.',
            ])->save();
            $pipeline->record($post, 'reject', 'ai', $post->mod_reason, null, $meta);

            return;
        }

        if ($score >= $hold) {
            $post->forceFill([
                'status' => Post::STATUS_HELD,
                'mod_source' => 'ai',
                'mod_reason' => $result['reason'] ?: 'AI: perlu tinjauan manusia.',
            ])->save();
            $pipeline->record($post, 'hold', 'ai', $post->mod_reason, null, $meta);

            return;
        }

        // Aman → tampil.
        $pipeline->approve($post, 'ai', $result['reason'] ?: 'AI: aman.');
    }
}
