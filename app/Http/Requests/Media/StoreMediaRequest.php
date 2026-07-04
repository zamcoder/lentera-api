<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', 'in:audio,photo'],
            'blob' => ['required', 'string'],       // base64 ciphertext media
            'nonce' => ['nullable', 'string'],
            'mime' => ['nullable', 'string', 'max:100'],
        ];
    }
}
