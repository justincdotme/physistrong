<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('log_rep_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained('workout_entries')->cascadeOnDelete();
            $table->unsignedInteger('target_reps')->nullable();
            $table->unsignedInteger('actual_reps')->nullable();
            $table->boolean('to_failure')->default(false);
            $table->unsignedInteger('failure_rep')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_rep_metrics');
    }
};
