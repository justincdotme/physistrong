<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_exercises', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained('workout_templates')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('exercise_order');
            $table->foreignId('template_entry_group_id')->nullable()->constrained('template_entry_groups')->nullOnDelete();
            $table->primary(['template_id', 'exercise_id']);
            $table->index(['template_id', 'exercise_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_exercises');
    }
};
