<?php

declare(strict_types=1);

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
        'distance'   => 'exercise_distance',
        'interval'   => 'exercise_interval',
    ];

    private const SPECIALTY_GEAR = [
        'Sled Push'                       => 'sled',
        'Sled Drag - Harness'             => 'sled',
        'Bear Crawl Sled Drags'           => 'sled',
        'Sled Overhead Triceps Extension' => 'sled',
        'Sled Reverse Flye'               => 'sled',
        'Sled Row'                        => 'sled',
        'Sledgehammer Swings'             => 'sled',
        'Sled Overhead Backward Walk'     => 'sled',
        'Dips - Chest Version'            => 'dip station',
        'Dips - Triceps Version'          => 'dip station',
        'Ring Dips'                       => 'dip station',
        'Rope Climb'                      => 'rope',
        'Rope Jumping'                    => 'rope',
        'Atlas Stone Trainer'             => 'atlas stone',
        'Atlas Stones'                    => 'atlas stone',
        'Log Lift'                        => 'log bar',
        'Axle Deadlift'                   => 'log bar',
        'Parallel Bar Dip'                => 'parallel bars',
        'Knee/Hip Raise On Parallel Bars' => 'parallel bars',
        'Battling Ropes'                  => 'battle rope',
        'Tire Flip'                       => 'tire',
        'Sandbag Load'                    => 'sandbag',
        'Keg Load'                        => 'keg',
        'Yoke Walk'                       => 'yoke',
        'Prowler Sprint'                  => 'prowler sled',
        'Ab Roller'                       => 'ab roller',
        'Wrist Roller'                    => 'wrist roller',
        'Balance Board'                   => 'balance board',
    ];

    public function test_seeds_full_catalog_across_all_cti_types(): void
    {
        $this->seedLibrary();

        $fixture = $this->fixtureExercises();
        $this->assertSame(count($fixture), DB::table('exercises')->count());

        // Seeded exercises are system-owned.
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

    public function test_every_equipment_reference_resolves_to_a_catalog_entry(): void
    {
        $catalog   = $this->fixtureEquipment();
        $exercises = $this->fixtureExercises();

        $unresolvedNames = [];

        foreach ($exercises as $exercise) {
            if ($exercise['equipment'] !== null && ! in_array($exercise['equipment'], $catalog, true)) {
                $unresolvedNames[] = "{$exercise['name']} -> {$exercise['equipment']}";
            }
        }

        $this->assertSame([], $unresolvedNames, 'exercises reference equipment types not in the catalog');
    }

    public function test_specialty_gear_exercises_resolve_to_correct_equipment(): void
    {
        $this->seedLibrary();

        $equipmentIds = DB::table('equipment_types')
            ->whereNull('user_id')
            ->pluck('id', 'name');

        $mismatches = [];

        foreach (self::SPECIALTY_GEAR as $exerciseName => $equipmentName) {
            $expectedId = $equipmentIds[$equipmentName] ?? null;

            if ($expectedId === null) {
                $mismatches[] = "equipment type '{$equipmentName}' missing from catalog";

                continue;
            }

            $actualId = DB::table('exercises')->where('name', $exerciseName)->value('equipment_type_id');

            if ($actualId !== $expectedId) {
                $mismatches[] = "'{$exerciseName}' resolves to " . var_export($actualId, true) . ", expected '{$equipmentName}'";
            }
        }

        $this->assertSame([], $mismatches);
    }

    public function test_reseeding_is_idempotent(): void
    {
        $this->seedLibrary();
        $before = $this->tableCounts();

        $this->seedLibrary();

        $this->assertSame($before, $this->tableCounts());
    }

    public function test_equipment_type_seeder_preserves_created_at_on_reseed(): void
    {
        $this->seed(EquipmentTypeSeeder::class);

        $firstRow = DB::table('equipment_types')->whereNull('user_id')->first();
        $this->assertNotNull($firstRow);

        $backdatedCreatedAt = now()->subYear();

        DB::table('equipment_types')
            ->where('id', $firstRow->id)
            ->update(['created_at' => $backdatedCreatedAt, 'is_system' => false]);

        $this->seed(EquipmentTypeSeeder::class);

        $afterReseed = DB::table('equipment_types')->where('id', $firstRow->id)->first();
        $this->assertSame(
            $backdatedCreatedAt->toDateTimeString(),
            $afterReseed->created_at,
            'created_at must not drift on reseed',
        );
        $this->assertTrue((bool) $afterReseed->is_system, 'mutable field is_system must still update');
    }

    public function test_exercise_library_seeder_preserves_created_at_on_reseed(): void
    {
        $this->seedLibrary();

        $exerciseWithEquipment = DB::table('exercises')
            ->whereNull('user_id')
            ->whereNotNull('equipment_type_id')
            ->first();
        $this->assertNotNull($exerciseWithEquipment, 'need an exercise with equipment to test mutable field update');

        $backdatedCreatedAt = now()->subYear();

        DB::table('exercises')
            ->where('id', $exerciseWithEquipment->id)
            ->update(['created_at' => $backdatedCreatedAt, 'equipment_type_id' => null]);

        $childTable = self::CHILD_TABLES[$exerciseWithEquipment->type];
        DB::table($childTable)
            ->where('exercise_id', $exerciseWithEquipment->id)
            ->update(['created_at' => $backdatedCreatedAt]);

        $this->seed(ExerciseLibrarySeeder::class);

        $afterReseed = DB::table('exercises')->where('id', $exerciseWithEquipment->id)->first();
        $this->assertSame(
            $backdatedCreatedAt->toDateTimeString(),
            $afterReseed->created_at,
            'exercise base created_at must not drift on reseed',
        );
        $this->assertNotNull($afterReseed->equipment_type_id, 'mutable field equipment_type_id must still update');

        $afterReseedChild = DB::table($childTable)->where('exercise_id', $exerciseWithEquipment->id)->first();
        $this->assertSame(
            $backdatedCreatedAt->toDateTimeString(),
            $afterReseedChild->created_at,
            'child table created_at must not drift on reseed',
        );
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
            'equipment_types'     => DB::table('equipment_types')->count(),
            'exercises'           => DB::table('exercises')->count(),
            'exercise_resistance' => DB::table('exercise_resistance')->count(),
            'exercise_timed_hold' => DB::table('exercise_timed_hold')->count(),
            'exercise_distance'   => DB::table('exercise_distance')->count(),
            'exercise_interval'   => DB::table('exercise_interval')->count(),
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
     * @param list<array{name: string, type: string, equipment: string|null, attributes: array<string, mixed>}> $exercises
     *
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
