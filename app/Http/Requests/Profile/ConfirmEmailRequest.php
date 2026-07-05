<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/** POST /profile/email/confirm — verifikasi kode & pasang email. */
class ConfirmEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string'],
        ];
    }
}
