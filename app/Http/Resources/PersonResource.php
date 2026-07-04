<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PersonResource — bentuk Person (§3). Field terenkripsi dikembalikan sebagai
 * base64 ciphertext (device yang mendekripsi → name/rel/recall/initial).
 * Server hanya menyajikan metadata plaintext untuk sort.
 */
class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_enc' => $this->b64($this->name_enc),
            'name_nonce' => $this->b64($this->name_nonce),
            'rel_enc' => $this->b64($this->rel_enc),
            'rel_nonce' => $this->b64($this->rel_nonce),
            'recall_enc' => $this->b64($this->recall_enc),
            'recall_nonce' => $this->b64($this->recall_nonce),
            'avatar_color' => $this->avatar_color,
            // Metadata plaintext (sort/tampilan ringkas).
            'pos_count' => $this->pos_count,
            'neg_count' => $this->neg_count,
            'last_at' => $this->last_at,
            'last_type' => $this->last_type,
            'created_at' => $this->created_at,
        ];
    }

    private function b64(?string $bytes): ?string
    {
        return $bytes === null ? null : base64_encode($bytes);
    }
}
