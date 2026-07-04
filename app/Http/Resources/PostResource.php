<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PostResource — bentuk Post komunitas (§7), selaras models.dart:
 * anon/author/avatar/avatarPal/time/text + Reactions(peluk,kekuatan,paham).
 * Komunitas = plaintext (bukan E2E). Jumlah reaksi disertakan (keputusan tim).
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'anon' => (bool) $this->anon,
            'author' => $this->anon ? $this->pseudonym : $this->author?->handle,
            'avatar' => $this->avatar,
            'avatar_pal' => $this->avatar_pal,
            'time' => $this->shortAgo($this->published_at ?? $this->created_at),
            'text' => $this->body,
            'reactions' => [
                'peluk' => (int) ($this->peluk ?? 0),
                'kekuatan' => (int) ($this->kekuatan ?? 0),
                'paham' => (int) ($this->paham ?? 0),
            ],
            'my_reactions' => $this->whenLoaded('reactions', fn () => $this->reactions->pluck('kind')->values(), []),
            'circle' => $this->whenLoaded('circle', fn () => $this->circle?->theme),
            'strength' => (bool) $this->strength,
            'status' => $this->status,
        ];
    }

    /** Relatif singkat berbahasa Indonesia (mis. "5 mnt", "1 jam"). */
    private function shortAgo(?\Illuminate\Support\Carbon $t): string
    {
        if (! $t) {
            return 'baru';
        }
        $s = $t->diffInSeconds(now());
        if ($s < 60) {
            return 'baru';
        }
        if ($s < 3600) {
            return floor($s / 60).' mnt';
        }
        if ($s < 86400) {
            return floor($s / 3600).' jam';
        }

        return floor($s / 86400).' hari';
    }
}
