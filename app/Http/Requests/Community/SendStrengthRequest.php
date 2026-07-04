<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Kirim kekuatan" — HANYA pesan siap-pakai (tanpa teks bebas), instan.
 */
class SendStrengthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', Rule::in((array) config('lentera.strength_messages'))],
        ];
    }
}
