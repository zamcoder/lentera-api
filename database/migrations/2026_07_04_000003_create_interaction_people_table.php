<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * interaction_people — pivot momen ↔ orang (§4 `person_ids[]`). Satu momen bisa
 * menandai beberapa orang; menjadi sumber metadata pos_count/neg_count/last_*.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interaction_people', function (Blueprint $table) {
            $table->uuid('interaction_id');
            $table->uuid('person_id');

            $table->foreign('interaction_id')->references('id')->on('interactions')->cascadeOnDelete();
            $table->foreign('person_id')->references('id')->on('people')->cascadeOnDelete();
            $table->primary(['interaction_id', 'person_id']);
            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interaction_people');
    }
};
