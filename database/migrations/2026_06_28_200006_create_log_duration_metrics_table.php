<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('log_duration_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained('workout_entries')->cascadeOnDelete();
            $table->unsignedInteger('target_duration_seconds')->nullable();
            $table->unsignedInteger('actual_duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_duration_metrics');
    }
};
