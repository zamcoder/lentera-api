<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VaultBackup — cadangan jurnal terenkripsi (ciphertext). Server buta.
 * `ciphertext` disembunyikan dari serialisasi JSON default agar tak sengaja
 * ikut terkirim di respons yang tak seharusnya.
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
        'user_id', 'ciphertext', 'version', 'size_bytes', 'checksum',
    ];

    protected $hidden = ['ciphertext'];

    protected function casts(): array
    {
        return [
            'ciphertext' => \App\Casts\Bytea::class,
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
