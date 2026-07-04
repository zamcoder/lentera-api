<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PostHide — kiriman yang disembunyikan seorang user dari feed-nya (§7).
 */
class PostHide extends Model
{
    protected $table = 'post_hides';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_id', 'post_id'];
}
