<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Upserts system equipment types so ids stay stable across re-seeds. */
class EquipmentTypeSeeder extends Seeder
{
    /** @return void */
    public function run(): void
    {
        $now = now();

        foreach ($this->catalog() as $name) {
            $existing = DB::table('equipment_types')
                ->where('name', $name)
                ->whereNull('user_id')
                ->exists();

            $payload = ['is_system' => true, 'updated_at' => $now];

            if (! $existing) {
                $payload['created_at'] = $now;
            }

            DB::table('equipment_types')->updateOrInsert(
                ['name' => $name, 'user_id' => null],
                $payload,
            );
        }
    }

    /**
     * @return list<string>
     */
    private function catalog(): array
    {
        $path  = database_path('seeders/data/equipment_types.json');
        $names = json_decode((string) file_get_contents($path), true);

        if (! is_array($names)) {
            return [];
        }

        return array_values(array_map('strval', $names));
    }
}
