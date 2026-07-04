<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan pengguna (§12): pengingat lembut malam (opt-in), aksen & tema.
 * sync_on sudah ada (§2). Panic-lock murni klien — tak butuh kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('reminder_on')->default(false);
            $table->time('reminder_at')->nullable();      // mis. 21:00
            $table->string('accent')->default('sage');    // aksen warna
            $table->string('theme')->default('system');   // light | dark | system
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reminder_on', 'reminder_at', 'accent', 'theme']);
        });
    }
};
