<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('log_intensity_metrics', function (Blueprint $table) {
            $table->renameColumn('avg_hr', 'heart_rate_avg');
            $table->renameColumn('max_hr', 'heart_rate_peak');
        });
    }

    public function down(): void
    {
        Schema::table('log_intensity_metrics', function (Blueprint $table) {
            $table->renameColumn('heart_rate_avg', 'avg_hr');
            $table->renameColumn('heart_rate_peak', 'max_hr');
        });
    }
};
