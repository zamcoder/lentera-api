<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update parsial — bila name_enc dikirim, name_nonce wajib menyertai (begitu
 * pula pasangan rel & recall).
 */
class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_enc' => ['sometimes', 'string', 'required_with:name_nonce'],
            'name_nonce' => ['sometimes', 'string', 'required_with:name_enc'],
            'rel_enc' => ['nullable', 'string', 'required_with:rel_nonce'],
            'rel_nonce' => ['nullable', 'string', 'required_with:rel_enc'],
            'recall_enc' => ['nullable', 'string', 'required_with:recall_nonce'],
            'recall_nonce' => ['nullable', 'string', 'required_with:recall_enc'],
            'avatar_color' => ['nullable', 'string', 'max:32'],
        ];
    }
}
