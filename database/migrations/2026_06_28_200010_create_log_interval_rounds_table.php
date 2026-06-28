<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('log_interval_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interval_header_id')->constrained('log_interval_headers')->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            $table->unsignedInteger('actual_work_seconds')->nullable();
            $table->unsignedInteger('actual_rest_seconds')->nullable();
            $table->unsignedInteger('heart_rate_avg')->nullable();
            $table->unsignedInteger('heart_rate_peak')->nullable();
            $table->timestamps();
            $table->unique(['interval_header_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_interval_rounds');
    }
};
