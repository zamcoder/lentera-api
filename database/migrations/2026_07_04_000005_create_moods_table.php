<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * moods — mood harian (§6). Metadata NON-sensitif: indeks mood 0..4 + tanggal.
 * Satu mood per hari per user (upsert). Dipakai kalender & tren, tanpa menyentuh
 * isi jurnal yang terenkripsi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->date('mood_date');
            $table->smallInteger('mood_index');            // 0 Berat .. 4 Cerah
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'mood_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moods');
    }
};
