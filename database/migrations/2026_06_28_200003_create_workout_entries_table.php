<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('set_order');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workout_id', 'set_order']);
            $table->index(['exercise_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_entries');
    }
};
