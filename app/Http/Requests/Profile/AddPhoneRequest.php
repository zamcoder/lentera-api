<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/** POST /profile/phone — minta OTP WhatsApp untuk menambah/mengganti nomor. */
class AddPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // E.164 (boleh dengan/tanpa +), 8–15 digit — sama dgn login OTP.
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
        ];
    }
}
