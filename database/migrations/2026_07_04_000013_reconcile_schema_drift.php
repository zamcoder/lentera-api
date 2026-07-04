<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan environment yang menjalankan versi awal beberapa migration
 * (sebelum kolom-kolom ini ditambahkan pada file migration). Idempoten via
 * Schema::hasColumn — aman untuk fresh install maupun prod lama.
 *
 * Memperbaiki:
 *  - posts.avatar / avatar_pal / strength  → POST /community/posts (500)
 *  - users.sync_on                          → PUT /settings/sync (500)
 *  - vault_backups.ciphertext / version     → PUT /vault/backup (500)
 *  - buang kolom escrow desain lama (NOT NULL, tak dipakai, memblok insert)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (! Schema::hasColumn('posts', 'avatar_pal')) {
                $table->string('avatar_pal')->nullable();
            }
            if (! Schema::hasColumn('posts', 'strength')) {
                $table->boolean('strength')->default(false);
            }
        });

        if (! Schema::hasColumn('users', 'sync_on')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('sync_on')->default(true);
            });
        }

        Schema::table('vault_backups', function (Blueprint $table) {
            if (! Schema::hasColumn('vault_backups', 'ciphertext')) {
                $table->binary('ciphertext')->nullable();
            }
            if (! Schema::hasColumn('vault_backups', 'version')) {
                $table->unsignedInteger('version')->default(1);
            }
        });

        foreach (['blob', 'escrow_enabled', 'key_escrow'] as $dead) {
            if (Schema::hasColumn('vault_backups', $dead)) {
                Schema::table('vault_backups', fn (Blueprint $t) => $t->dropColumn($dead));
            }
        }
    }

    public function down(): void
    {
        // Tidak menurunkan; kolom yang ditambah tak berbahaya untuk ditinggalkan.
    }
};
