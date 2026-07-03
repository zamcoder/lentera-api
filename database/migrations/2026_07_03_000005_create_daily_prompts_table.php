<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * daily_prompts — satu pertanyaan bersama per hari (§03, GET /prompt/today).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('prompt_date')->unique();
            $table->text('body');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_prompts');
    }
};
