<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * post_hides — "Sembunyikan dari feed-ku" (§7). Per-user; kiriman yang
 * disembunyikan tak muncul lagi di feed pengguna itu (tak menghapus kiriman).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_hides', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('post_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->primary(['user_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_hides');
    }
};
