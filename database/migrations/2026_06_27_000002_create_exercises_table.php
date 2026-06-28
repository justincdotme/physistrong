<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            // null user_id marks a system-seeded exercise shared by all users (ADR-010).
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Block-if-used on equipment deletion is enforced at the application layer (ADR-002);
            // restrictOnDelete reinforces it at the database level.
            $table->foreignId('equipment_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
