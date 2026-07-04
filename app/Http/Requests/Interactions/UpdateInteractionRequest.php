<?php

namespace App\Http\Requests\Interactions;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInteractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'in:positive,negative,neutral'],
            'text_enc' => ['sometimes', 'string', 'required_with:text_nonce'],
            'text_nonce' => ['sometimes', 'string', 'required_with:text_enc'],
            'topic' => ['nullable', 'string', 'max:60'],
            'mood' => ['nullable', 'integer', 'between:0,4'],
            'occurred_at' => ['nullable', 'date'],
            'person_ids' => ['nullable', 'array'],
            'person_ids.*' => ['uuid'],
        ];
    }
}
