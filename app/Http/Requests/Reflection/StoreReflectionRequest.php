<?php

namespace App\Http\Requests\Reflection;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Body PUT /reflections/{date} — semua field opsional (user boleh isi 1 baris).
 * Nilai = base64(ciphertext‖tag) / base64(nonce). 'sometimes' agar field yang
 * tak dikirim TIDAK menimpa nilai lama saat upsert.
 */
class StoreReflectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $field = ['sometimes', 'nullable', 'string'];

        return [
            'grateful_enc' => $field, 'grateful_nonce' => $field,
            'drained_enc' => $field, 'drained_nonce' => $field,
            'tomorrow_enc' => $field, 'tomorrow_nonce' => $field,
        ];
    }
}
