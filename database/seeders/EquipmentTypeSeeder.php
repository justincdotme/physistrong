<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the system equipment-type catalog (ADR-004).
 *
 * System types carry a null user_id and is_system = true. Upsert keeps ids
 * stable so exercise foreign keys survive a re-seed.
 */
class EquipmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ($this->catalog() as $name) {
            DB::table('equipment_types')->updateOrInsert(
                ['name' => $name, 'user_id' => null],
                ['is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    /**
     * @return list<string>
     */
    private function catalog(): array
    {
        $path = database_path('seeders/data/equipment_types.json');
        $names = json_decode((string) file_get_contents($path), true);

        if (! is_array($names)) {
            return [];
        }

        return array_values(array_map('strval', $names));
    }
}
