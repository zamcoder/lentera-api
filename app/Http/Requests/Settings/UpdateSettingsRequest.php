<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sync_on' => ['sometimes', 'boolean'],
            'reminder_on' => ['sometimes', 'boolean'],
            'reminder_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'accent' => ['sometimes', 'string', 'max:20'],
            'theme' => ['sometimes', 'in:light,dark,system'],
        ];
    }
}
