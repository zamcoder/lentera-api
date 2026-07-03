<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BannedTerm extends Model
{
    use HasUuids;

    protected $table = 'banned_terms';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['pattern', 'is_regex', 'action', 'hits', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_regex' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
