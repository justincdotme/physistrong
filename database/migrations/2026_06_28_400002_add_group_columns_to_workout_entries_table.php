<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('workout_entries', function (Blueprint $table) {
            $table->foreignId('entry_group_id')
                ->nullable()
                ->after('exercise_id')
                ->constrained('entry_groups')
                ->nullOnDelete();
            $table->unsignedInteger('group_round')->nullable()->after('entry_group_id');
            $table->index(['entry_group_id', 'group_round', 'set_order']);
        });
    }

    public function down(): void
    {
        Schema::table('workout_entries', function (Blueprint $table) {
            $table->dropIndex(['entry_group_id', 'group_round', 'set_order']);
            $table->dropConstrainedForeignId('entry_group_id');
            $table->dropColumn('group_round');
        });
    }
};
