<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reports — laporan pengguna atas sebuah kiriman (§06/§08). Masuk ke konsol (§07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('reporter_id')->nullable();   // nullable: pelapor anonim
            $table->string('reason');                  // spam | harassment | self_harm | other ...
            $table->text('note')->nullable();
            $table->string('status')->default('open'); // open | resolved | dismissed
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('resolved_at')->nullable();

            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('reporter_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
