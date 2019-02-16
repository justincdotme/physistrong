<?php

namespace Tests\Unit;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\ExerciseWorkout;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;

    public function setUp()
    {
        parent::setUp();
        $this->workout = factory(Workout::class)->create();
        $sets = collect([
            factory(Set::class)->make([
                'id' => 5,
                'exercise_id' => 1,
                'set_order' => 2
            ]),
            factory(Set::class)->make([
                'id' =>41,
                'exercise_id' => 1,
                'set_order' => 1
            ]),
            factory(Set::class)->make([
                'id' => 3,
                'exercise_id' => 3,
                'set_order' => 3
            ]),
            factory(Set::class)->make([
                'id' => 2,
                'exercise_id' => 3,
                'set_order' => 1
            ]),
            factory(Set::class)->make([
                'id' => 1,
                'exercise_id' => 3,
                'set_order' => 2
            ]),

        ]);

        $this->workout->sets()->saveMany($sets);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_exercise_workout()
    {
        $exerciseWorkout = factory(ExerciseWorkout::class)->make([
            'exercise_id' => 1,
            'exercise_order' => 1
        ]);

        $this->workout->exerciseWorkout()->save($exerciseWorkout);

        $this->assertInstanceOf(ExerciseWorkout::class, $this->workout->exerciseWorkout()->first());
    }

    /**
     * @test
     */
    public function it_has_relationship_to_user()
    {
        $user = factory(User::class)->create();

        $this->workout->user()->associate($user);

        $this->assertInstanceOf(User::class, $this->workout->user()->first());
    }

    /**
     * @test
     */
    public function it_has_relationship_to_sets()
    {
        $set = factory(Set::class)->create();

        $this->workout->sets()->save($set);

        $this->assertInstanceOf(Set::class, $this->workout->sets()->first());
    }

    /**
     * @test
     */
    public function sets_are_ordered_by_set_order_and_exercise_id()
    {
        $workoutSets = $this->workout->sets()->get();

        $this->assertEquals(1, $workoutSets->first()->set_order);
        $this->assertEquals(1, $workoutSets->first()->exercise_id);

        $middleSet = $workoutSets->slice(2,1)->first();
        $this->assertEquals(1, $middleSet->set_order);
        $this->assertEquals(3, $middleSet->exercise_id);

        $this->assertEquals(3, $workoutSets->last()->set_order);
        $this->assertEquals(3, $workoutSets->last()->exercise_id);
    }
}
