<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PromptAnswer — jawaban Prompt bersama (§9). Plaintext, dimoderasi.
 */
class PromptAnswer extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_APPROVED = 'approved';

    protected $table = 'prompt_answers';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'prompt_id', 'author_id', 'text', 'anon', 'pseudonym', 'avatar', 'avatar_pal',
        'status', 'mod_source', 'mod_reason', 'self_harm', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'anon' => 'boolean',
            'self_harm' => 'boolean',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_APPROVED)->whereNotNull('published_at');
    }
}
