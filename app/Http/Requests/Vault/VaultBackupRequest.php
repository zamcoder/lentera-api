<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;

class VaultBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ciphertext' => ['required', 'string'],          // base64 ciphertext dari device
            'version' => ['nullable', 'integer', 'min:1'],   // bump tiap ubah (opsional)
            'checksum' => ['nullable', 'string', 'max:128'],
        ];
    }
}
