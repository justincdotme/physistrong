<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use ExerciseTypesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TypeTest extends TestCase
{
    use DatabaseMigrations;

    protected $exercise;

    /**
     * @test
     */
    public function it_has_relationship_to_exercise()
    {
        $this->seed(ExerciseTypesTableSeeder::class);
        $this->exercise = factory(Exercise::class)->make([
            'id' => 1,
            'name' => 'Squat',
        ]);
        $workout = factory(Workout::class)->create();
        $this->exercise->workouts()->save($workout, ['exercise_order' => 1]);

        $this->assertCount(1, $this->exercise->workouts);
    }
}
