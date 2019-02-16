<?php

namespace Tests\Unit;

use App\Core\Set;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SetTest extends TestCase
{
    use DatabaseMigrations;

    protected $bench;
    protected $squat;
    protected $workout;

    public function setUp()
    {
        parent::setUp();
        $this->workout = factory(Workout::class)->create();

        $this->squat = factory(Exercise::class)->create([
            'name' => 'Squat'
        ]);
        $this->bench = factory(Exercise::class)->create([
            'name' => 'Bench Press'
        ]);

        $this->sets = collect([
            Set::forWorkout($this->workout, $this->squat, 2, 120, 10),
            Set::forWorkout($this->workout, $this->squat, 1, 140, 8),
            Set::forWorkout($this->workout, $this->bench, 2, 80, 10),
            Set::forWorkout($this->workout, $this->bench, 1, 120, 6)
        ]);
    }

    /**
     * @test
     */
    public function user_can_add_multiple_sets_of_same_exercise()
    {
        $this->assertCount(2, $this->workout->sets()->ofExercise($this->bench)->get());
    }

    /**
     * @test
     */
    public function user_can_get_sets_of_specific_exercise()
    {
        $this->assertCount(2, $this->workout->sets()->ofExercise($this->squat)->get());
    }

    /**
     * @test
     */
    public function sets_of_specific_exercise_are_ordered_by_set_order()
    {
        $sets = $this->workout->sets()->ofExercise($this->squat)->get();
        $this->assertEquals(1, $sets->first()->set_order);
        $this->assertEquals(2, $sets->last()->set_order);
    }
}
