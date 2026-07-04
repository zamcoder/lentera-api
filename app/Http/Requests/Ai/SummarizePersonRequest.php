<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class SummarizePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'relation' => ['nullable', 'string', 'max:120'],
            'pos_count' => ['nullable', 'integer', 'min:0'],
            'neg_count' => ['nullable', 'integer', 'min:0'],
            'interactions' => ['nullable', 'array', 'max:200'],
            'interactions.*.type' => ['nullable', 'string', 'max:20'],
            'interactions.*.text' => ['nullable', 'string', 'max:2000'],
            'interactions.*.date' => ['nullable', 'string', 'max:40'],
        ];
    }
}
