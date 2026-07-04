<?php

namespace App\Models;

use App\Casts\Bytea;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Media — blob suara/foto terenkripsi (§5). Server buta. blob_enc disembunyikan
 * dari serialisasi default.
 */
class Media extends Model
{
    use HasUuids;

    protected $table = 'media';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'interaction_id', 'kind', 'blob_enc', 'nonce', 'mime', 'size_bytes',
    ];

    protected $hidden = ['blob_enc', 'nonce'];

    protected function casts(): array
    {
        return [
            'blob_enc' => Bytea::class,
            'nonce' => Bytea::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }
}
