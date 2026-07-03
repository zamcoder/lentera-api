<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VaultBackup — cadangan jurnal terenkripsi (ciphertext). Server buta.
 * `blob` & `key_escrow` disembunyikan dari serialisasi JSON default agar
 * ciphertext tak sengaja ikut terkirim di respons yang tak seharusnya.
 */
class VaultBackup extends Model
{
    use HasUuids;

    protected $table = 'vault_backups';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id', 'blob', 'key_escrow', 'escrow_enabled', 'size_bytes', 'checksum',
    ];

    protected $hidden = ['blob', 'key_escrow'];

    protected function casts(): array
    {
        return [
            'blob' => \App\Casts\Bytea::class,
            'key_escrow' => \App\Casts\Bytea::class,
            'escrow_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
