<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vault_backups — cadangan jurnal pribadi (§05 Bidang A, opsional).
 *
 * KENAPA BYTEA: isinya ciphertext AES-256-GCM yang dienkripsi di device.
 * Server menyimpan blob mentah "buta" — tak punya kunci, tak bisa & tak boleh
 * mendekripsi. bytea menyimpan byte biner apa adanya tanpa asumsi encoding
 * (berbeda dari TEXT yang mengharuskan UTF-8 valid) — cocok untuk ciphertext.
 *
 * key_escrow: titipan kunci pemulihan (§05 trade-off). NULL = mode
 * "tanpa pemulihan, lebih privat"; terisi = pengguna memilih pemulihan
 * dibantu server. Tetap terenkripsi terhadap wrapping-key sisi server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_backups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->binary('blob');                    // BYTEA — ciphertext jurnal
            $table->binary('key_escrow')->nullable();  // BYTEA — kunci pemulihan (opsional)
            $table->boolean('escrow_enabled')->default(false);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable();    // integritas (mis. sha256 hex)
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // Satu cadangan aktif per user (upsert saat PUT /vault/backup).
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_backups');
    }
};
