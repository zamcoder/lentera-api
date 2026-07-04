<?php

namespace App\Http\Requests\Stats;

use Illuminate\Foundation\Http\FormRequest;

class SetMoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mood_index' => ['required', 'integer', 'between:0,4'],
            'date' => ['nullable', 'date'],
        ];
    }
}
