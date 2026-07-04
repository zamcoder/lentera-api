<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Mood extends Model
{
    use HasUuids;

    protected $table = 'moods';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id', 'mood_date', 'mood_index'];

    protected function casts(): array
    {
        return [
            'mood_date' => 'date',
            'mood_index' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
