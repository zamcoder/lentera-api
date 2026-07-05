<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/** POST /profile/phone/confirm — verifikasi OTP & pasang nomor. */
class ConfirmPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'code' => ['required', 'string'],
        ];
    }
}
