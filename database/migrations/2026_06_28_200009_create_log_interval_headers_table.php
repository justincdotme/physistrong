<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('log_interval_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained('workout_entries')->cascadeOnDelete();
            $table->unsignedInteger('programmed_rounds')->nullable();
            $table->unsignedInteger('completed_rounds')->nullable();
            $table->unsignedInteger('target_work_seconds')->nullable();
            $table->unsignedInteger('target_rest_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_interval_headers');
    }
};
