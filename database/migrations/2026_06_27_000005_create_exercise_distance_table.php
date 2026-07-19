<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_distance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('distance_unit', ['meters', 'kilometers', 'miles', 'yards']);
            $table->boolean('tracks_elevation')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_distance');
    }
};
