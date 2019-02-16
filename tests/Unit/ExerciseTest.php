<?php

namespace Tests\Unit;

use Exception;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExerciseTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $exercise1;
    protected $exercise2;

    public function setUp()
    {
        parent::setUp();
        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1,
            'name' => 'Squat'
        ]);
        $this->exercise2 = factory(Exercise::class)->create([
            'id' => 2,
            'name' => 'Bench Press'
        ]);
        $this->workout = factory(Workout::class)->create();
    }

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $this->exercise1->workouts()->save($this->workout, ['exercise_order' => 1]);

        $this->assertCount(1, $this->exercise1->workouts);
    }

    /**
     * @test
     */
    public function duplicate_exercises_are_not_allowed_on_a_workout()
    {
        try {
            $this->exercise1->workouts()->save($this->workout, ['exercise_order' => 1]);
            $this->exercise1->workouts()->save($this->workout, ['exercise_order' => 2]);
        } catch (Exception $e) {
            $this->assertInstanceOf(QueryException::class, $e);
            return;
        }
        $this->fail('Duplicate exercises were added to a workout');
    }

    /**
     * @test
     */
    public function multiple_unique_exercises_are_allowed_on_a_workout()
    {
        $this->exercise1->workouts()->save($this->workout, ['exercise_order' => 1]);
        $this->exercise2->workouts()->save($this->workout, ['exercise_order' => 2]);

        $this->assertCount(2, $this->workout->exercises()->get());
    }
}
