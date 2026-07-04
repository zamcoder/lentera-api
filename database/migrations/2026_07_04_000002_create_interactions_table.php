<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * interactions — momen/log jurnal (§4, Bidang A E2E).
 *
 * Isi catatan (text) terenkripsi (BYTEA + nonce) — server buta. Metadata
 * non-sensitif plaintext: `type` (0 netral/1 positif/2 negatif), `topic`,
 * `mood`, `occurred_at` — untuk filter, urut timeline & statistik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');

            $table->smallInteger('type')->default(0);     // 0 netral / 1 positif / 2 negatif
            $table->binary('text_enc');                    // ciphertext catatan
            $table->binary('text_nonce');
            $table->string('topic')->nullable();           // kategori non-sensitif
            $table->smallInteger('mood')->nullable();      // 0..4 (opsional)
            $table->timestampTz('occurred_at')->useCurrent(); // kapan momen terjadi (plaintext)

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
