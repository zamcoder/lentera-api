<?php

namespace App\Models;

use App\Casts\Bytea;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Person — orang di hidup pengguna (§3). Field sensitif terenkripsi (BYTEA),
 * metadata plaintext untuk sort. Server tak pernah mendekripsi.
 */
class Person extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'people';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'name_enc', 'name_nonce',
        'rel_enc', 'rel_nonce',
        'recall_enc', 'recall_nonce',
        'avatar_color', 'pos_count', 'neg_count', 'last_at', 'last_type',
    ];

    protected function casts(): array
    {
        return [
            'name_enc' => Bytea::class,
            'name_nonce' => Bytea::class,
            'rel_enc' => Bytea::class,
            'rel_nonce' => Bytea::class,
            'recall_enc' => Bytea::class,
            'recall_nonce' => Bytea::class,
            'pos_count' => 'integer',
            'neg_count' => 'integer',
            'last_type' => 'integer',
            'last_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
