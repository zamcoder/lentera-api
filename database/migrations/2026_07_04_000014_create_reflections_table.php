<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reflections — "Tiga baris malam" (§6, E2E). Tiga field terenkripsi di device
 * (yang kusyukuri / yang menguras / harapan besok) + nonce terpisah. Server buta,
 * hanya menyimpan ciphertext. Satu baris per (user, tanggal) — upsert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->date('reflection_date');

            foreach (['grateful', 'drained', 'tomorrow'] as $f) {
                $table->binary("{$f}_enc")->nullable();     // BYTEA ciphertext
                $table->binary("{$f}_nonce")->nullable();   // IV 96-bit
            }

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'reflection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflections');
    }
};
