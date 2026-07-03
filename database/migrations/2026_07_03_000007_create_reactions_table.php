<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reactions — reaksi hangat, BUKAN komentar (§03). Hanya tiga jenis lembut:
 * hug (peluk) · strength (kekuatan) · understand (aku paham).
 * Tak ada teks bebas → menghapus pintu masuk perundungan.
 * Unik per (post, user, kind): satu orang satu reaksi per jenis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('user_id');
            $table->string('kind');                // hug | strength | understand
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['post_id', 'user_id', 'kind']);
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
