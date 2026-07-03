<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    use HasUuids;

    public const KIND_HUG = 'hug';
    public const KIND_STRENGTH = 'strength';
    public const KIND_UNDERSTAND = 'understand';
    public const KINDS = [self::KIND_HUG, self::KIND_STRENGTH, self::KIND_UNDERSTAND];

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
