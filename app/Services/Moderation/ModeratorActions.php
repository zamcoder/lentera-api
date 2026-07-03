<?php

namespace App\Services\Moderation;

use App\Models\Post;
use App\Models\Report;
use App\Services\AuditLogger;

/**
 * ModeratorActions — menerapkan keputusan moderator dari konsol (§A5/§B3).
 * Setiap aksi menulis jejak moderation_actions + audit_log, dan menyelesaikan
 * laporan terbuka pada kiriman itu.
 */
class ModeratorActions
{
    public function __construct(
        private readonly ModerationPipeline $pipeline,
        private readonly BannedWordFilter $filter,
        private readonly AuditLogger $audit,
    ) {
    }

    public const ACTIONS = ['approve', 'soften', 'reject', 'hold', 'escalate', 'offer_support'];

    public function apply(Post $post, string $action, string $moderatorId, ?string $reason = null): Post
    {
        match ($action) {
            'approve' => $this->pipeline->approve($post, 'manual', $reason ?? 'Disetujui moderator.', $moderatorId),
            'soften' => $this->soften($post, $moderatorId, $reason),
            'reject' => $this->finalize($post, Post::STATUS_REJECTED, 'reject', $moderatorId, $reason ?? 'Ditolak moderator.'),
            'hold' => $this->finalize($post, Post::STATUS_HELD, 'hold', $moderatorId, $reason ?? 'Ditahan untuk tinjauan.'),
            'escalate' => $this->finalize($post, Post::STATUS_ESCALATED, 'escalate', $moderatorId, $reason ?? 'Dieskalasi.'),
            'offer_support' => $this->offerSupport($post, $moderatorId, $reason),
            default => abort(422, 'Aksi tidak dikenal.'),
        };

        $this->resolveReports($post);
        $this->audit->log($moderatorId, "mod.{$action}", 'post', $post->id, ['reason' => $reason]);

        return $post->fresh();
    }

    private function soften(Post $post, string $moderatorId, ?string $reason): void
    {
        // "Haluskan": mask kata kasar lalu terbitkan.
        $scan = $this->filter->scan($post->body);
        if ($scan['masked'] || $scan['blocked']) {
            $post->body = $scan['text'];
            $post->masked = true;
        }
        $this->pipeline->approve($post, 'manual', $reason ?? 'Dihaluskan & disetujui.', $moderatorId);
    }

    private function offerSupport(Post $post, string $moderatorId, ?string $reason): void
    {
        // Isyarat menyakiti diri: tetap tahan dari publik, tandai penanganan
        // khusus. Penawaran bantuan ke pengguna dilakukan klien (§06).
        $post->forceFill([
            'status' => Post::STATUS_HELD,
            'self_harm' => true,
            'mod_reason' => $reason ?? 'Penanganan khusus — dukungan ditawarkan.',
        ])->save();
        $this->pipeline->record($post, 'offer_support', 'manual', $reason, $moderatorId, ['self_harm' => true]);
    }

    private function finalize(Post $post, string $status, string $action, string $moderatorId, string $reason): void
    {
        $post->forceFill([
            'status' => $status,
            'mod_source' => 'manual',
            'mod_reason' => $reason,
            'published_at' => $status === Post::STATUS_APPROVED ? now() : null,
        ])->save();
        $this->pipeline->record($post, $action, 'manual', $reason, $moderatorId);
    }

    private function resolveReports(Post $post): void
    {
        Report::where('post_id', $post->id)
            ->where('status', 'open')
            ->update(['status' => 'resolved', 'resolved_at' => now()]);
    }
}
