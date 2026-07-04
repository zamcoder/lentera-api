<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'uuid', 'exists:posts,id'],
            // Alasan identik reportReasons app.
            'reason' => ['required', Rule::in((array) config('lentera.report_reasons'))],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
