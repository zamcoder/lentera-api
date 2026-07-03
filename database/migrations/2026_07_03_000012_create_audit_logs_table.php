<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs — jejak audit tindakan admin/moderator (§A6): siapa/aksi/target/waktu.
 * Menangkap tindakan konsol yang lebih luas dari sekadar moderasi kiriman —
 * termasuk tindakan akun (bisukan/batasi/blokir) dan perubahan kata terlarang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id')->nullable();      // moderator/admin
            $table->string('action');                  // mod.approve, account.block, term.create ...
            $table->string('target_type')->nullable(); // post | user | term | report
            $table->uuid('target_id')->nullable();
            $table->jsonb('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['actor_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
