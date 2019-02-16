<?php

namespace Tests\Unit;

use App\Core\Exercise;
use App\Core\Set;
use App\Core\User;
use Exception;
use Illuminate\Database\QueryException;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $exercise1;
    protected $exercise2;

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

        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1
        ]);
        $this->exercise2 = factory(Exercise::class)->create([
            'id' => 2
        ]);

        $this->workout->sets()->saveMany($sets);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_exercises()
    {
        $this->workout->exercises()->save($this->exercise1, ['exercise_order' => 2]);
        $this->workout->exercises()->save($this->exercise2, ['exercise_order' => 1]);

        $this->assertCount(2, $this->workout->exercises);
    }



    /**
     * @test
     */
    public function exercises_are_ordered_by_exercise_order()
    {
        $this->workout->exercises()->save($this->exercise1, ['exercise_order' => 2]);
        $this->workout->exercises()->save($this->exercise2, ['exercise_order' => 1]);

        $this->assertEquals(1, $this->workout->exercises->first()->pivot->exercise_order);
        $this->assertEquals(2, $this->workout->exercises->first()->id);
        $this->assertEquals(2, $this->workout->exercises->last()->pivot->exercise_order);
        $this->assertEquals(1, $this->workout->exercises->last()->id);
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
