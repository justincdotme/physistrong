<?php

namespace Tests\Feature;

use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\ExerciseLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExerciseLibrarySeederTest extends TestCase
{
    use RefreshDatabase;

    private const CHILD_TABLES = [
        'resistance' => 'exercise_resistance',
        'timed_hold' => 'exercise_timed_hold',
        'distance' => 'exercise_distance',
        'interval' => 'exercise_interval',
    ];

    public function test_seeds_full_catalog_across_all_cti_types(): void
    {
        $this->seedLibrary();

        $fixture = $this->fixtureExercises();
        $this->assertSame(count($fixture), DB::table('exercises')->count());

        // Seeded exercises are system-owned (ADR-010).
        $this->assertSame(0, DB::table('exercises')->whereNotNull('user_id')->count());

        $expectedByType = $this->countByType($fixture);
        $this->assertCount(4, $expectedByType, 'all four CTI types must be represented');

        foreach ($expectedByType as $type => $count) {
            $this->assertSame(
                $count,
                DB::table('exercises')->where('type', $type)->count(),
                "base row count for {$type}",
            );
            // Class Table Inheritance: one child row per base exercise of that type.
            $this->assertSame(
                $count,
                DB::table(self::CHILD_TABLES[$type])->count(),
                "child row count for {$type}",
            );
        }
    }

    public function test_equipment_catalog_is_system_owned_and_resolved_by_name(): void
    {
        $this->seedLibrary();

        $catalog = $this->fixtureEquipment();
        $this->assertSame(count($catalog), DB::table('equipment_types')->count());
        $this->assertSame(
            count($catalog),
            DB::table('equipment_types')->whereNull('user_id')->where('is_system', true)->count(),
        );

        // An exercise tagged with equipment resolves to that type's FK.
        $withEquipment = collect($this->fixtureExercises())->firstWhere('equipment', '!==', null);
        $this->assertNotNull($withEquipment);
        $expectedId = DB::table('equipment_types')
            ->whereNull('user_id')
            ->where('name', $withEquipment['equipment'])
            ->value('id');
        $this->assertSame(
            $expectedId,
            DB::table('exercises')->where('name', $withEquipment['name'])->value('equipment_type_id'),
        );

        // A bodyweight exercise carries no equipment.
        $bodyweight = collect($this->fixtureExercises())->first(fn ($e) => $e['equipment'] === null);
        $this->assertNotNull($bodyweight);
        $this->assertNull(
            DB::table('exercises')->where('name', $bodyweight['name'])->value('equipment_type_id'),
        );
    }

    public function test_reseeding_is_idempotent(): void
    {
        $this->seedLibrary();
        $before = $this->tableCounts();

        $this->seedLibrary();

        $this->assertSame($before, $this->tableCounts());
    }

    private function seedLibrary(): void
    {
        $this->seed(EquipmentTypeSeeder::class);
        $this->seed(ExerciseLibrarySeeder::class);
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(): array
    {
        return [
            'equipment_types' => DB::table('equipment_types')->count(),
            'exercises' => DB::table('exercises')->count(),
            'exercise_resistance' => DB::table('exercise_resistance')->count(),
            'exercise_timed_hold' => DB::table('exercise_timed_hold')->count(),
            'exercise_distance' => DB::table('exercise_distance')->count(),
            'exercise_interval' => DB::table('exercise_interval')->count(),
        ];
    }

    /**
     * @return list<array{name: string, type: string, equipment: string|null, attributes: array<string, mixed>}>
     */
    private function fixtureExercises(): array
    {
        $decoded = json_decode((string) file_get_contents(database_path('seeders/data/exercises.json')), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private function fixtureEquipment(): array
    {
        $decoded = json_decode((string) file_get_contents(database_path('seeders/data/equipment_types.json')), true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    /**
     * @param  list<array{name: string, type: string, equipment: string|null, attributes: array<string, mixed>}>  $exercises
     * @return array<string, int>
     */
    private function countByType(array $exercises): array
    {
        $counts = [];
        foreach ($exercises as $exercise) {
            $counts[$exercise['type']] = ($counts[$exercise['type']] ?? 0) + 1;
        }

        return $counts;
    }
}
