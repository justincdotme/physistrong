<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_distance', function (Blueprint $table) {
            $table->dropColumn('distance_unit');
        });
    }

    // The dropped units are unrecoverable; a nullable column keeps rollback
    // working on populated tables.
    public function down(): void
    {
        Schema::table('exercise_distance', function (Blueprint $table) {
            $table->string('distance_unit')->nullable();
        });
    }
};
