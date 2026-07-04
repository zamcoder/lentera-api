<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * people — orang di hidup pengguna (§3, Bidang A jurnal E2E).
 *
 * Field sensitif (nama, relasi, catatan "recall") disimpan sebagai BYTEA
 * ciphertext + nonce — device yang mengenkripsi (AES-256-GCM), server buta.
 * Metadata NON-sensitif (pos_count, neg_count, last_at, last_type) plaintext
 * agar bisa dipakai mengurutkan tanpa membongkar privasi. Metadata ini
 * diturunkan dari interactions (dipelihara saat momen ditambah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');

            // Terenkripsi (ciphertext + nonce 96-bit).
            $table->binary('name_enc');
            $table->binary('name_nonce');
            $table->binary('rel_enc')->nullable();
            $table->binary('rel_nonce')->nullable();
            $table->binary('recall_enc')->nullable();
            $table->binary('recall_nonce')->nullable();

            // Non-sensitif untuk UI/sort.
            $table->string('avatar_color')->nullable();      // pal/warna avatar
            $table->unsignedInteger('pos_count')->default(0); // dari interactions
            $table->unsignedInteger('neg_count')->default(0);
            $table->timestampTz('last_at')->nullable();
            $table->smallInteger('last_type')->nullable();    // 0 netral / 1 positif / 2 negatif

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'last_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
