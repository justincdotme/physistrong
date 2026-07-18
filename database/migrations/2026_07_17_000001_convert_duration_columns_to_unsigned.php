<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('exercise_timed_hold', function (Blueprint $table) {
            $table->unsignedInteger('target_duration_seconds')->nullable()->change();
        });

        Schema::table('exercise_interval', function (Blueprint $table) {
            $table->unsignedInteger('default_work_seconds')->nullable()->change();
            $table->unsignedInteger('default_rest_seconds')->nullable()->change();
            $table->unsignedInteger('default_rounds')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exercise_timed_hold', function (Blueprint $table) {
            $table->integer('target_duration_seconds')->nullable()->change();
        });

        Schema::table('exercise_interval', function (Blueprint $table) {
            $table->integer('default_work_seconds')->nullable()->change();
            $table->integer('default_rest_seconds')->nullable()->change();
            $table->integer('default_rounds')->nullable()->change();
        });
    }
};
