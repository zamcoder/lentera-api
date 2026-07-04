<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'anon' => ['boolean'],
            // 'strength' = kiriman "sedang berat" (struggle) → masuk antrean Kirim kekuatan.
            'surface' => ['nullable', 'in:gratitude,circle,prompt,strength'],
            'circle_id' => ['nullable', 'uuid', 'required_if:surface,circle', 'exists:circles,id'],
            'prompt_id' => ['nullable', 'uuid', 'exists:daily_prompts,id'],
        ];
    }
}
