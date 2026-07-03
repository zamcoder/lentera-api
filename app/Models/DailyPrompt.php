<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DailyPrompt extends Model
{
    use HasUuids;

    protected $table = 'daily_prompts';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['prompt_date', 'body'];

    protected function casts(): array
    {
        return ['prompt_date' => 'date'];
    }
}
