<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'handle' => ['nullable', 'string', 'max:40', 'unique:users,handle'],
            // Salt KDF dibuat di device untuk menurunkan kunci E2E (base64).
            'kdf_salt' => ['nullable', 'string'],
        ];
    }
}
