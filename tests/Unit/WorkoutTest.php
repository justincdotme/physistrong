<?php

namespace Tests\Unit;

use App\Core\Set;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * @test
     */
    public function sets_are_grouped_by_exercise()
    {
        $workout = factory(Workout::class)->create();
        $exercise1 = factory(Exercise::class)->make([
            'name' => 'Bench Press',
            'id' => 1
        ]);
        $exercise2 = factory(Exercise::class)->make([
            'name' => 'Squat',
            'id' => 2
        ]);
        $sets = collect([
            factory(Set::class)->make([
                'weight' => 120,
                'count' => 10,
                'exercise_id' => $exercise1->id,
                'created_at' => Carbon::now()->subMinutes(20)->toDateTimeString()
            ]),
            factory(Set::class)->make([
                'weight' => 140,
                'count' => 7,
                'exercise_id' => $exercise1->id,
                'created_at' => Carbon::now()->subMinutes(15)->toDateTimeString()
            ]),
            factory(Set::class)->make([
                'weight' => 145,
                'count' => 6,
                'exercise_id' => $exercise1->id,
                'created_at' => Carbon::now()->subMinutes(10)->toDateTimeString()
            ]),
            factory(Set::class)->make([
                'weight' => 120,
                'count' => 10,
                'exercise_id' => $exercise2->id,
                'created_at' => Carbon::now()->subMinutes(22)->toDateTimeString()
            ]),
            factory(Set::class)->make([
                'weight' => 140,
                'count' => 7,
                'exercise_id' => $exercise2->id,
                'created_at' => Carbon::now()->subMinutes(17)->toDateTimeString()
            ]),
            factory(Set::class)->make([
                'weight' => 145,
                'count' => 6,
                'exercise_id' => $exercise2->id,
                'created_at' => Carbon::now()->subMinutes(12)->toDateTimeString()
            ]),
        ]);

        $workout->sets()->saveMany($sets);

        $exerciseOneCollection =  $workout->getSets()
            ->first()
            ->each(function ($item, $key) use ($exercise1) {
                $this->assertEquals($exercise1->id, $item->exercise_id);
            }
        );
        $this->assertGreaterThan(
            $exerciseOneCollection->last()->created_at->toDateTimeString(),
            $exerciseOneCollection->first()->created_at->toDateTimeString()
        );
        $exerciseTwoCollection =  $workout->getSets()
            ->last()
            ->each(function ($item, $key) use ($exercise2) {
                $this->assertEquals($exercise2->id, $item->exercise_id);
            }
        );
        $this->assertGreaterThan(
            $exerciseTwoCollection->last()->created_at->toDateTimeString(),
            $exerciseTwoCollection->first()->created_at->toDateTimeString()
        );
    }
}
