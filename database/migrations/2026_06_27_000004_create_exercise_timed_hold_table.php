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
        Schema::create('exercise_timed_hold', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('target_duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_timed_hold');
    }
};
