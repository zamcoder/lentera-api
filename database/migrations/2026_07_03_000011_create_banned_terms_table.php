<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * banned_terms — kamus pola regex terlarang (§06 Lapis 1). Dikelola admin,
 * cocok = blok/mask instan. "hits" mencatat berapa kali menahan sesuatu
 * (ditampilkan "× ditahan" di konsol).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banned_terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pattern')->unique();       // regex / kata
            $table->boolean('is_regex')->default(false);
            $table->string('action')->default('block'); // block | mask
            $table->unsignedInteger('hits')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_terms');
    }
};
