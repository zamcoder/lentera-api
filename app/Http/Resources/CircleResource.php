<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CircleResource — bentuk Circle app (§8): name/emoji/desc/pal/members/joined.
 * `members` = jumlah anggota terformat ("1,2rb"); `member_count` = angka mentah.
 */
class CircleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->theme,
            'emoji' => $this->emoji,
            'desc' => $this->description,
            'pal' => $this->pal,
            'member_count' => (int) $this->member_count,
            'members' => $this->formatMembers((int) $this->member_count),
            'joined' => (int) ($this->joined_count ?? 0) > 0,
        ];
    }

    private function formatMembers(int $n): string
    {
        return $n < 1000 ? (string) $n : number_format($n / 1000, 1, ',', '.').'rb';
    }
}
