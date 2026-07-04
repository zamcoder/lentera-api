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
            // ID token dari penyedia — WAJIB diverifikasi server (bukan sub mentah).
            'id_token' => ['required', 'string'],
        ];
    }
}
