<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * auth_identities — empat cara masuk (§04/§08): email, phone, google, apple.
 * Satu user bisa punya banyak identitas; tiap (provider, identifier) unik global.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('provider');        // email | phone | google | apple
            $table->string('identifier');      // alamat email / no HP / subject OAuth
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['provider', 'identifier']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_identities');
    }
};
