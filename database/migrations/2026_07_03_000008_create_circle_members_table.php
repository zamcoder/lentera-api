<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * circle_members — keanggotaan lingkaran (§08). Unik per (circle, user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circle_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('circle_id');
            $table->uuid('user_id');
            $table->timestampTz('joined_at')->useCurrent();

            $table->foreign('circle_id')->references('id')->on('circles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['circle_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circle_members');
    }
};
