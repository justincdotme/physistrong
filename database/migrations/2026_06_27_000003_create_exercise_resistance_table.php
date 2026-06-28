<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercise_resistance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('bodyweight_base')->default(false);
            $table->boolean('allows_added_weight')->default(false);
            $table->boolean('bilateral')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_resistance');
    }
};
