<?php

namespace App\Services\Moderation;

use App\Jobs\ClassifyPostJob;
use App\Models\ModerationAction;
use App\Models\Post;

/**
 * ModerationPipeline — dua lapis (§06).
 *
 * Bagian SINKRON (dipanggil saat POST /posts):
 *   0. Isyarat menyakiti diri → held + penanganan khusus (bukan blokir dingin).
 *   1. Lapis 1 regex banned_terms → blok/mask instan.
 *   2. "Kirim kekuatan" pesan siap-pakai → setujui instan (tanpa antrean).
 *   3. Selebihnya → dispatch Job Lapis 2 (Gemini) via Queue.
 */
class ModerationPipeline
{
    public function __construct(
        private readonly BannedWordFilter $filter,
        private readonly SelfHarmDetector $selfHarm,
    ) {
    }

    /**
     * @return array{status: string, self_harm: bool, queued: bool, safe_space?: array}
     */
    public function moderateSync(Post $post): array
    {
        // 0 — Keselamatan dulu: isyarat menyakiti diri (§06 penanganan khusus).
        if ($this->selfHarm->detect($post->body)) {
            $post->forceFill([
                'status' => Post::STATUS_HELD,
                'self_harm' => true,
                'mod_source' => 'regex',
                'mod_reason' => 'Isyarat menyakiti diri — penanganan khusus.',
            ])->save();

            $this->record($post, 'hold', 'regex', 'Isyarat menyakiti diri terdeteksi.', null, ['self_harm' => true]);

            // Tetap dispatch Lapis 2 agar moderator dapat konteks AI di antrean,
            // tapi kiriman sudah pasti tertahan dari publik.
            ClassifyPostJob::dispatch($post->id);

            return [
                'status' => Post::STATUS_HELD,
                'self_harm' => true,
                'queued' => true,
                'safe_space' => config('lentera.safe_space'),
            ];
        }

        // 1 — Lapis 1: kamus kata terlarang.
        $scan = $this->filter->scan($post->body);
        $this->filter->recordHits($scan['matched']);

        if ($scan['blocked']) {
            $post->forceFill([
                'status' => Post::STATUS_REJECTED,
                'mod_source' => 'regex',
                'mod_reason' => 'Mengandung kata yang tidak diperbolehkan.',
            ])->save();

            $this->record($post, 'reject', 'regex', 'Cocok daftar kata terlarang.');

            return ['status' => Post::STATUS_REJECTED, 'self_harm' => false, 'queued' => false];
        }

        if ($scan['masked']) {
            $post->body = $scan['text'];
            $post->masked = true;
        }

        // 2 — "Kirim kekuatan": pesan siap-pakai → instan, tanpa antrean.
        if ($post->surface === Post::SURFACE_STRENGTH && $this->isReadyMessage($post->body)) {
            $this->approve($post, 'auto', 'Pesan kekuatan siap-pakai (pra-tayang).');

            return ['status' => Post::STATUS_APPROVED, 'self_harm' => false, 'queued' => false];
        }

        // 3 — Lapis 2 asinkron (Gemini) via Queue.
        $post->status = Post::STATUS_PENDING;
        $post->save();
        ClassifyPostJob::dispatch($post->id);

        return ['status' => Post::STATUS_PENDING, 'self_harm' => false, 'queued' => true];
    }

    public function approve(Post $post, string $source = 'manual', ?string $reason = null, ?string $moderatorId = null): void
    {
        $post->forceFill([
            'status' => Post::STATUS_APPROVED,
            'masked' => $post->masked,
            'published_at' => now(),
        ])->save();

        $this->record($post, 'approve', $source, $reason, $moderatorId);
    }

    public function record(Post $post, string $action, string $source, ?string $reason = null, ?string $moderatorId = null, ?array $meta = null): ModerationAction
    {
        return ModerationAction::create([
            'post_id' => $post->id,
            'moderator_id' => $moderatorId,
            'action' => $action,
            'source' => $source,
            'reason' => $reason,
            'meta' => $meta,
        ]);
    }

    private function isReadyMessage(string $body): bool
    {
        $ready = array_map('trim', (array) config('lentera.strength_messages'));

        return in_array(trim($body), $ready, true);
    }
}
