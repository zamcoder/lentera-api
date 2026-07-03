<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * posts — kiriman komunitas (§03/§08). Bidang B: TERSIMPAN PLAINTEXT
 * (bukan E2E) justru agar bisa dimoderasi (§05/§06). Setiap kiriman melewati
 * pipa moderasi sebelum tampil.
 *
 * surface  : gratitude (Dinding Syukur) | strength (Kirim kekuatan)
 *            | prompt (Prompt bersama)  | circle (Lingkaran)
 * status   : pending | approved | held | rejected | escalated
 * self_harm: penanganan khusus (§06) — tahan dari publik + sinyal ke klien,
 *            BUKAN blokir dingin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('author_id');
            $table->uuid('circle_id')->nullable();     // hanya untuk surface=circle
            $table->uuid('prompt_id')->nullable();     // hanya untuk surface=prompt

            $table->string('surface');                 // gratitude | strength | prompt | circle
            $table->text('body');
            $table->boolean('anon')->default(true);    // default anonim (§03)
            $table->string('pseudonym')->nullable();   // nama samaran lembut bila anon

            // Moderasi (§06).
            $table->string('status')->default('pending');
            $table->string('mod_source')->nullable();  // regex | ai | manual
            $table->text('mod_reason')->nullable();     // alasan AI/regex untuk konsol
            $table->boolean('masked')->default(false);  // "dihaluskan" (kata di-mask)
            $table->boolean('self_harm')->default(false); // isyarat menyakiti diri
            $table->timestampTz('published_at')->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('circle_id')->references('id')->on('circles')->nullOnDelete();
            $table->foreign('prompt_id')->references('id')->on('daily_prompts')->nullOnDelete();

            // Feed per surface, hanya yang approved, terbaru dulu.
            $table->index(['surface', 'status', 'published_at']);
            $table->index(['circle_id', 'status', 'published_at']);
            $table->index(['status', 'created_at']);   // antrean moderasi
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
