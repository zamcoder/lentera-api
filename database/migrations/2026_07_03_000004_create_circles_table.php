<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * circles — lingkaran kecil bertema (§03), mis. "menjaga batas".
 * Intim & terbatas; anggota tercatat di circle_members.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('theme');               // judul lingkaran
            $table->text('description')->nullable();
            $table->unsignedInteger('member_count')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
