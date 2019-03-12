<?php

use App\Core\Workout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WorkoutsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Workout::class)->create([
            'user_id' => 1,
            'name' => 'Leg Day',
            'date_scheduled' => Carbon::now()->toDateString(),
        ]);

        factory(Workout::class)->create([
            'user_id' => 1,
            'name' => 'Chest & Shoulders',
            'date_scheduled' => Carbon::now()->addDays(2)->toDateString(),
        ]);

        factory(Workout::class)->create([
            'user_id' => 1,
            'name' => 'Back & Calves',
            'date_scheduled' => Carbon::now()->addDays(4)->toDateString(),
        ]);
    }
}
