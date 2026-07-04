<?php

namespace App\Models;

use App\Casts\Bytea;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Reflection — refleksi harian E2E ("Tiga baris malam", §6). Field *_enc/*_nonce
 * disimpan sebagai BYTEA; server tak pernah mendekripsi.
 */
class Reflection extends Model
{
    use HasUuids;

    protected $table = 'reflections';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id', 'reflection_date',
        'grateful_enc', 'grateful_nonce',
        'drained_enc', 'drained_nonce',
        'tomorrow_enc', 'tomorrow_nonce',
    ];

    protected function casts(): array
    {
        return [
            'reflection_date' => 'date',
            'grateful_enc' => Bytea::class,
            'grateful_nonce' => Bytea::class,
            'drained_enc' => Bytea::class,
            'drained_nonce' => Bytea::class,
            'tomorrow_enc' => Bytea::class,
            'tomorrow_nonce' => Bytea::class,
        ];
    }
}
