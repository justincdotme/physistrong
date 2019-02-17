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

    protected $sets;
    protected $plank;
    protected $squat;
    protected $workout;

    public function setUp()
    {
        parent::setUp();
        $this->workout = factory(Workout::class)->create();

        $this->squat = factory(Exercise::class)->create([
            'name' => 'Squat',
            'exercise_type_id' => 1,
        ]);
        $this->plank = factory(Exercise::class)->create([
            'exercise_type_id' => 2,
            'name' => 'Plank'
        ]);

        $this->sets = collect([
            Set::forWorkout($this->workout, $this->squat, 2, 120, 10),
            Set::forWorkout($this->workout, $this->squat, 1, 140, 8),
            Set::forWorkout($this->workout, $this->plank, 2, 80, 10),
            Set::forWorkout($this->workout, $this->plank, 1, 120, 6)
        ]);
    }

    /**
     * @test
     */
    public function user_can_add_multiple_sets_of_same_exercise()
    {
        $this->assertCount(2, $this->workout->sets()->ofExercise($this->plank)->get());
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

    /**
     * @test
     */
    public function it_can_show_formatted_weight()
    {
        $bodyWeightSet = factory(Set::class)->make([
            'weight' => 0
        ]);
        $weightedSet = factory(Set::class)->make([
            'weight' => 10
        ]);

        $this->assertEquals('Body', $bodyWeightSet->weight);
        $this->assertEquals(10, $weightedSet->weight);
    }
}
