<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Subscriber — email waitlist landing page. created_at diisi default DB;
 * tak butuh updated_at.
 */
class Subscriber extends Model
{
    public $timestamps = false;

    protected $fillable = ['email', 'source', 'ip'];
}
