<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users — akun server (§08 Rencana Produk + §3 Handoff Doc).
 *
 * Gabungan dua sumber kebenaran:
 *  - §08: id, handle (pseudonim), status, created_at
 *  - Handoff SQL: email (citext), password_hash (argon2id), kdf_salt (derive
 *    kunci E2E di device), totp_secret_enc (2FA konsol).
 *
 * Catatan privasi: kolom kripto (kdf_salt, totp_secret_enc) TIDAK memberi
 * server akses ke jurnal — kdf_salt hanya membantu device menurunkan kunci;
 * kunci sesungguhnya tak pernah menyentuh server.
 */
return new class extends Migration
{
    public function up(): void
    {
        // citext = case-insensitive text, agar email unik tanpa peduli kapital.
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS citext');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Identitas publik komunitas (pseudonim lembut). Wajib unik.
            $table->string('handle')->unique();

            // Login klasik (email + sandi). Nullable: akun bisa lewat HP/OAuth saja.
            // Dibuat string dulu; diubah ke citext setelah tabel jadi (lihat bawah).
            $table->string('email')->nullable()->unique();
            $table->string('password_hash')->nullable();     // argon2id

            // Kripto sisi-device untuk jurnal E2E (server tak pernah pakai untuk dekripsi).
            $table->binary('kdf_salt')->nullable();          // BYTEA
            $table->binary('totp_secret_enc')->nullable();   // BYTEA — 2FA konsol
            $table->boolean('totp_enabled')->default(false);

            // Peran & status moderasi akun.
            $table->string('role')->default('user');         // user | admin
            $table->string('status')->default('active');     // active | muted | limited | blocked

            $table->rememberToken();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->index('status');
            $table->index('role');
        });

        // Jadikan email case-insensitive (citext) agar unik tanpa peduli kapital.
        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');

        // Reset sandi bawaan Laravel (dipakai jalur email + recover).
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
