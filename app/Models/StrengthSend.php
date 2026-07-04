<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * StrengthSend — kiriman "Kirim kekuatan" (§9), instan tanpa pra-tayang.
 */
class StrengthSend extends Model
{
    use HasUuids;

    protected $table = 'strength_sends';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['sender_id', 'post_id', 'message'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
