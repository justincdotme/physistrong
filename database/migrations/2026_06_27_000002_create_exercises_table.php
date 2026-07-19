<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            // null user_id marks a system-seeded exercise shared by all users.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Block-if-used on equipment deletion is enforced at the application layer;
            // restrictOnDelete reinforces it at the database level.
            $table->foreignId('equipment_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
