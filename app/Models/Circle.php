<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Circle extends Model
{
    use HasUuids;

    protected $table = 'circles';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['slug', 'theme', 'emoji', 'pal', 'description', 'member_count', 'created_by'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(CircleMember::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
