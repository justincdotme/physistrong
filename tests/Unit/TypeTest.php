<?php

namespace Tests\Unit;

use ExerciseTypesTableSeeder;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TypeTest extends TestCase
{
    use DatabaseMigrations;

    protected $exercise;

    public function setUp()
    {
        parent::setUp();
        $this->exercise = factory(Exercise::class)->make([
            'id' => 1,
            'name' => 'Squat',
        ]);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_exercise()
    {
        $this->seed(ExerciseTypesTableSeeder::class);
        $workout = factory(Workout::class)->create();

        $this->exercise->workouts()->save($workout, ['exercise_order' => 1]);

        $this->assertCount(1, $this->exercise->workouts);
    }
}
