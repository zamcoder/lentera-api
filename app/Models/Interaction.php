<?php

namespace App\Models;

use App\Casts\Bytea;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Interaction — momen jurnal (§4). Isi terenkripsi; type/occurred_at plaintext.
 */
class Interaction extends Model
{
    use HasFactory, HasUuids;

    // Selaras MomentType app (models.dart) & Handoff SQL (0 netral/1 positif/2 negatif).
    public const TYPE_MAP = ['neutral' => 0, 'positive' => 1, 'negative' => 2];
    public const TYPE_POSITIVE = 1;
    public const TYPE_NEGATIVE = 2;

    protected $table = 'interactions';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id', 'type', 'text_enc', 'text_nonce', 'topic', 'mood', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'text_enc' => Bytea::class,
            'text_nonce' => Bytea::class,
            'type' => 'integer',
            'mood' => 'integer',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function typeToInt(string $s): int
    {
        return self::TYPE_MAP[$s] ?? 0;
    }

    public static function typeToString(int $i): string
    {
        return array_search($i, self::TYPE_MAP, true) ?: 'neutral';
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'interaction_people', 'interaction_id', 'person_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
