<?php

namespace Tests\Feature;

use App\Core\Set;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CreateSetTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function exercise_sets_can_be_added_to_workout()
    {
        $exercise = factory(Exercise::class)->create([
            'id' => 1,
            'name' => 'Bench Press'
        ]);
        $workout = factory(Workout::class)->create([
            'id' => 1
        ]);

        $weight = 120;
        $count = 10;
        $set = Set::forWorkout($workout, $exercise, $weight, $count);

        $this->assertEquals(
            1,
            $set->workout->id
        );
        $this->assertEquals(
            1,
            $set->exercise->id
        );
        $this->assertEquals(
            120,
            $set->weight
        );
        $this->assertEquals(
            10,
            $set->count
        );
        $this->assertEquals(
            'Bench Press',
            $set->exercise->name
        );
    }
}
