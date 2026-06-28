<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('log_distance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained('workout_entries')->cascadeOnDelete();
            $table->decimal('target_distance', 10, 2)->nullable();
            $table->decimal('actual_distance', 10, 2)->nullable();
            $table->enum('distance_unit', ['meters', 'kilometers', 'miles', 'yards']);
            $table->unsignedInteger('lap_count')->nullable();
            $table->unsignedInteger('stroke_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_distance_metrics');
    }
};
