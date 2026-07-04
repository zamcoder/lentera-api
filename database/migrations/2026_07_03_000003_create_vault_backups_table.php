<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vault_backups — cadangan jurnal pribadi terenkripsi (§2 API_REQUIREMENTS).
 *
 * KENAPA BYTEA: isinya ciphertext AES-256-GCM yang dienkripsi di device.
 * Server menyimpan blob mentah "buta" — tak punya kunci, tak bisa & tak boleh
 * mendekripsi. bytea menyimpan byte biner apa adanya tanpa asumsi encoding
 * (berbeda dari TEXT yang mengharuskan UTF-8 valid) — cocok untuk ciphertext.
 *
 * Satu blob per user (versi di-bump tiap perubahan), sesuai API §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_backups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->binary('ciphertext');              // BYTEA — ciphertext jurnal
            $table->unsignedInteger('version')->default(1); // bump tiap ubah
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
