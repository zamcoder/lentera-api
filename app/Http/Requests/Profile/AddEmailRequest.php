<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/** POST /profile/email — minta kode verifikasi untuk menambah/mengganti email. */
class AddEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
