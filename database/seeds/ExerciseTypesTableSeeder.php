<?php

use App\Core\ExerciseType;
use Illuminate\Database\Seeder;

class ExerciseTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ExerciseType::create([
            'id' => 1,
            'name' => 'Dynamic'
        ]);
        ExerciseType::create([
            'id' => 2,
            'name' => 'Static'
        ]);
    }
}
