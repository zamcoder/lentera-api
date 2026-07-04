<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * prompt_answers — jawaban Prompt bersama (§9). Tabel terpisah (keputusan
 * kickoff). Bidang B plaintext, DIMODERASI seperti kiriman komunitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prompt_id');
            $table->uuid('author_id');

            $table->text('text');
            $table->boolean('anon')->default(true);
            $table->string('pseudonym')->nullable();
            $table->string('avatar')->nullable();
            $table->string('avatar_pal')->nullable();

            // Moderasi (§06/§10).
            $table->string('status')->default('pending');
            $table->string('mod_source')->nullable();
            $table->text('mod_reason')->nullable();
            $table->boolean('self_harm')->default(false);
            $table->timestampTz('published_at')->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('prompt_id')->references('id')->on('daily_prompts')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['prompt_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_answers');
    }
};
