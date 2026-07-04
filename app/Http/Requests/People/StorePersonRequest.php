<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Field *_enc & *_nonce dikirim device sebagai base64 ciphertext (E2E).
 */
class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_enc' => ['required', 'string'],
            'name_nonce' => ['required', 'string'],
            'rel_enc' => ['nullable', 'string', 'required_with:rel_nonce'],
            'rel_nonce' => ['nullable', 'string', 'required_with:rel_enc'],
            'recall_enc' => ['nullable', 'string', 'required_with:recall_nonce'],
            'recall_nonce' => ['nullable', 'string', 'required_with:recall_enc'],
            'avatar_color' => ['nullable', 'string', 'max:32'],
        ];
    }
}
