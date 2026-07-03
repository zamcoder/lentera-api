<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * moderation_actions — jejak keputusan moderasi atas kiriman (§08 "moderation").
 * Baik otomatis (regex/AI) maupun manual (moderator di konsol). Menjadi
 * riwayat "target + action" yang bisa ditelusuri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id')->nullable();
            $table->uuid('moderator_id')->nullable();  // null = otomatis (sistem)
            // approve | soften | reject | hold | escalate | offer_support
            $table->string('action');
            $table->string('source')->default('manual'); // regex | ai | manual
            $table->text('reason')->nullable();
            $table->jsonb('meta')->nullable();          // skor AI, kategori, dsb
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->nullOnDelete();
            $table->foreign('moderator_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');
    }
};
