<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * otp_codes — kode sekali-pakai untuk masuk via HP (POST /auth/otp) dan
 * jalur pemulihan (POST /auth/recover). Kode disimpan sebagai hash, tak plaintext.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier');          // no HP atau email
            $table->string('purpose');             // login_otp | recover
            $table->string('code_hash');           // hash SHA-256 dari kode
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['identifier', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
