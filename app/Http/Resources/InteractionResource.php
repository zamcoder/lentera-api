<?php

namespace App\Http\Resources;

use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * InteractionResource — bentuk Moment (§4). Isi terenkripsi (base64), metadata
 * plaintext. `type` sebagai string agar cocok MomentType app.
 */
class InteractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => Interaction::typeToString((int) $this->type),
            'text_enc' => base64_encode($this->text_enc),
            'text_nonce' => base64_encode($this->text_nonce),
            'topic' => $this->topic,
            'mood' => $this->mood,
            'person_ids' => $this->whenLoaded('people', fn () => $this->people->pluck('id')->values(), []),
            'media_ids' => $this->whenLoaded('media', fn () => $this->media->pluck('id')->values(), []),
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
        ];
    }
}
