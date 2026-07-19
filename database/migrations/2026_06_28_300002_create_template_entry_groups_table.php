<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_entry_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('workout_templates')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('planned_rounds')->default(1);
            $table->unsignedInteger('rest_between_exercises_seconds')->default(0);
            $table->unsignedInteger('rest_between_rounds_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_entry_groups');
    }
};
