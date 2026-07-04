<?php

namespace App\Http\Requests\Interactions;

use Illuminate\Foundation\Http\FormRequest;

class StoreInteractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:positive,negative,neutral'],
            'text_enc' => ['required', 'string'],       // base64 ciphertext
            'text_nonce' => ['required', 'string'],
            'topic' => ['nullable', 'string', 'max:60'],
            'mood' => ['nullable', 'integer', 'between:0,4'],
            'occurred_at' => ['nullable', 'date'],
            'person_ids' => ['nullable', 'array'],
            'person_ids.*' => ['uuid'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['uuid'],
        ];
    }
}
