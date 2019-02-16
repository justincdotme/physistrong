<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExerciseTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $workout = factory(Workout::class)->create();
        $exercise = factory(Exercise::class)->make([
            'id' => 1,
            'name' => 'Squat'
        ]);

        $exercise->workouts()->save($workout, ['exercise_order' => 1]);

        $this->assertCount(1, $exercise->workouts);
    }
}
