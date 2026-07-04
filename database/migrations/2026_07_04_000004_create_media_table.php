<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * media — suara & foto terenkripsi (§5, Bidang A E2E). Blob ciphertext BYTEA;
 * server tak pernah membaca. Diunggah lebih dulu (→ media_id), lalu dilampirkan
 * ke sebuah interaction via media_ids[].
 *
 * Catatan skala: untuk berkas besar, object storage terenkripsi bisa
 * menggantikan BYTEA nanti (lihat "perlu dikonfirmasi" di API_REQUIREMENTS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('interaction_id')->nullable();

            $table->string('kind');                 // audio | photo
            $table->binary('blob_enc');             // ciphertext media
            $table->binary('nonce')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('interaction_id')->references('id')->on('interactions')->nullOnDelete();
            $table->index(['user_id', 'interaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
