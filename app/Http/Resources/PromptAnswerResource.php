<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PromptAnswerResource — jawaban Prompt bersama (§9), bentuk mirip Post app.
 * Reaksi disertakan sebagai nol (jawaban prompt tak melacak reaksi di MVP).
 */
class PromptAnswerResource extends JsonResource
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
            'text' => $this->text,
            'reactions' => ['peluk' => 0, 'kekuatan' => 0, 'paham' => 0],
            'status' => $this->status,
        ];
    }

    private function shortAgo(?\Illuminate\Support\Carbon $t): string
    {
        if (! $t) {
            return 'baru';
        }
        $s = $t->diffInSeconds(now());

        return match (true) {
            $s < 60 => 'baru',
            $s < 3600 => floor($s / 60).' mnt',
            $s < 86400 => floor($s / 3600).' jam',
            default => floor($s / 86400).' hari',
        };
    }
}
