<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    use HasUuids;

    // Selaras reactionDefs app (dummy_data.dart): peluk · kekuatan · paham.
    public const KIND_PELUK = 'peluk';
    public const KIND_KEKUATAN = 'kekuatan';
    public const KIND_PAHAM = 'paham';
    public const KINDS = [self::KIND_PELUK, self::KIND_KEKUATAN, self::KIND_PAHAM];

    protected $table = 'reactions';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['post_id', 'user_id', 'kind'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
