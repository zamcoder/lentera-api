<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:google,apple'],
            'sub' => ['required', 'string', 'max:255'],   // subject unik dari penyedia
            'email' => ['nullable', 'email'],
        ];
    }
}
