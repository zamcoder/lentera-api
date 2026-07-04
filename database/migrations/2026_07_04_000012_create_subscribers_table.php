<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * subscribers — email waitlist dari landing page temanlentera.id.
 * email disimpan lowercase & unique (idempoten via firstOrCreate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email', 190)->unique();
            $table->string('source')->nullable()->default('landing');
            $table->string('ip')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
