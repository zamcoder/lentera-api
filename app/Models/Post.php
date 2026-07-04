<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Post — kiriman komunitas. Status moderasi menentukan apakah tampil publik.
 */
class Post extends Model
{
    use HasFactory, HasUuids;

    // Surface (permukaan komunitas, §03).
    public const SURFACE_GRATITUDE = 'gratitude';
    public const SURFACE_STRENGTH = 'strength';
    public const SURFACE_PROMPT = 'prompt';
    public const SURFACE_CIRCLE = 'circle';

    // Status moderasi (§06).
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_HELD = 'held';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ESCALATED = 'escalated';

    protected $table = 'posts';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'author_id', 'circle_id', 'prompt_id', 'surface', 'body',
        'anon', 'pseudonym', 'avatar', 'avatar_pal', 'strength',
        'status', 'mod_source', 'mod_reason',
        'masked', 'self_harm', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'anon' => 'boolean',
            'strength' => 'boolean',
            'masked' => 'boolean',
            'self_harm' => 'boolean',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function hides(): HasMany
    {
        return $this->hasMany(PostHide::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(ModerationAction::class);
    }

    /** Scope kiriman yang layak tampil publik. */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->whereNotNull('published_at');
    }
}
