<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the system exercise catalog from free-exercise-db (ADR-003, ADR-010).
 *
 * Each exercise is a system row (null user_id) plus one Class Table Inheritance
 * child row keyed by type. Equipment is resolved from the system catalog by name.
 * Must run after EquipmentTypeSeeder. Upsert on (user_id, name) makes re-seeding
 * safe and preserves ids.
 */
class ExerciseLibrarySeeder extends Seeder
{
    /** @var array<string, string> */
    private const CHILD_TABLES = [
        'resistance' => 'exercise_resistance',
        'timed_hold' => 'exercise_timed_hold',
        'distance' => 'exercise_distance',
        'interval' => 'exercise_interval',
    ];

    public function run(): void
    {
        $exercises = $this->load();
        if ($exercises === []) {
            return;
        }

        /** @var array<string, int> $equipmentIds */
        $equipmentIds = DB::table('equipment_types')
            ->whereNull('user_id')
            ->pluck('id', 'name')
            ->all();

        $now = now();

        DB::transaction(function () use ($exercises, $equipmentIds, $now): void {
            foreach ($exercises as $exercise) {
                $equipmentName = $exercise['equipment'];
                $equipmentTypeId = $equipmentName !== null ? ($equipmentIds[$equipmentName] ?? null) : null;

                DB::table('exercises')->updateOrInsert(
                    ['user_id' => null, 'name' => $exercise['name']],
                    [
                        'type' => $exercise['type'],
                        'equipment_type_id' => $equipmentTypeId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $exerciseId = DB::table('exercises')
                    ->whereNull('user_id')
                    ->where('name', $exercise['name'])
                    ->value('id');

                DB::table(self::CHILD_TABLES[$exercise['type']])->updateOrInsert(
                    ['exercise_id' => $exerciseId],
                    $exercise['attributes'] + ['created_at' => $now, 'updated_at' => $now],
                );
            }
        });
    }

    /**
     * @return list<array{name: string, type: string, equipment: string|null, attributes: array<string, mixed>}>
     */
    private function load(): array
    {
        $path = database_path('seeders/data/exercises.json');
        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        /** @var list<array{name: string, type: string, equipment: string|null, attributes: array<string, mixed>}> $decoded */
        return $decoded;
    }
}
