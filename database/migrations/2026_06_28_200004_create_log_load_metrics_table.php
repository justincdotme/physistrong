<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_load_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained('workout_entries')->cascadeOnDelete();
            $table->decimal('target_weight', 8, 2)->nullable();
            $table->decimal('actual_weight', 8, 2)->nullable();
            $table->boolean('bodyweight_only')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_load_metrics');
    }
};
