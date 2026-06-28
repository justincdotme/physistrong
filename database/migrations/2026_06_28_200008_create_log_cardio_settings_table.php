<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('log_cardio_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained('workout_entries')->cascadeOnDelete();
            $table->unsignedInteger('resistance_level')->nullable();
            $table->decimal('incline', 5, 2)->nullable();
            $table->decimal('speed', 5, 2)->nullable();
            $table->unsignedInteger('cadence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_cardio_settings');
    }
};
