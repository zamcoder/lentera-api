<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class SummarizeDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'string', 'max:40'],
            'mood_index' => ['nullable', 'integer', 'min:0', 'max:4'],
            'moments' => ['nullable', 'array', 'max:200'],
            'moments.*.type' => ['nullable', 'string', 'max:20'],
            'moments.*.text' => ['nullable', 'string', 'max:2000'],
            'moments.*.person' => ['nullable', 'string', 'max:120'],
        ];
    }
}
