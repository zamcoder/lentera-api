<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * strength_sends — "Kirim kekuatan" (§9): pesan template dukungan yang dikirim
 * ke sebuah kiriman-struggle. INSTAN, tanpa pra-tayang. Satu kiriman per
 * (pengirim, post) agar tak spam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strength_sends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sender_id');
            $table->uuid('post_id');
            $table->string('message');            // salah satu pesan siap-pakai
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->unique(['sender_id', 'post_id']);
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strength_sends');
    }
};
