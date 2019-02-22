<?php

namespace Tests\Unit;

use Exception;
use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Database\QueryException;
use App\Exceptions\UndeletableException;
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
    }

    /**
     * @test
     */
    public function it_has_relationship_to_exercises()
    {
        $exercises = collect([
            factory(Exercise::class)->make([
                'id' => 2
            ]),
            factory(Exercise::class)->make([
                'id' => 1
            ])
        ]);

        $this->workout->setRelation('exercises', $exercises);

        $this->assertCount(2, $this->workout->exercises);
    }



    /**
     * @test
     */
    public function it_has_relationship_to_user()
    {
        $user = factory(User::class)->make();

        $this->workout->setRelation('user', $user);

        $this->assertInstanceOf(User::class, $this->workout->user);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_sets()
    {
        $sets = collect([
            factory(Set::class)->make()
        ]);

        $this->workout->setRelation('sets', $sets);

        $this->assertCount(1, $this->workout->sets);
    }

    /**
     * @test
     */
    public function sets_are_ordered_by_set_order_and_exercise_id()
    {
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
    public function exercises_are_ordered_by_exercise_order()
    {
        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1
        ]);
        $this->exercise2 = factory(Exercise::class)->create([
            'id' => 2
        ]);

        $this->workout->addExercise($this->exercise1, 2);
        $this->workout->addExercise($this->exercise2, 1);

        $this->assertEquals(1, $this->workout->exercises->first()->pivot->exercise_order);
        $this->assertEquals(2, $this->workout->exercises->first()->id);
        $this->assertEquals(2, $this->workout->exercises->last()->pivot->exercise_order);
        $this->assertEquals(1, $this->workout->exercises->last()->id);
    }

    /**
     * @test
     */
    public function duplicate_exercises_are_not_allowed_on_a_workout()
    {
        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1
        ]);
        try {
            $this->workout->addExercise($this->exercise1, 1);
            $this->workout->addExercise($this->exercise1, 2);
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
        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1
        ]);
        $this->exercise2 = factory(Exercise::class)->create([
            'id' => 2
        ]);

        $this->workout->addExercise($this->exercise1, 1);
        $this->workout->addExercise($this->exercise2, 2);

        $this->assertCount(2, $this->workout->exercises()->get());
    }

    /**
     * @test
     */
    public function exercises_without_sets_can_be_removed_from_workout()
    {
        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1
        ]);

        $this->workout->removeExercise($this->exercise1);

        $this->assertCount(0, $this->workout->exercises);
    }

    /**
     * @test
     */
    public function exercises_with_sets_cannot_be_removed_from_workout()
    {
        $this->exercise1 = factory(Exercise::class)->create([
            'id' => 1
        ]);
        $set = factory(Set::class)->create([
            'exercise_id' => $this->exercise1->id
        ]);

        $this->workout->addExercise($this->exercise1, 1);
        $this->workout->sets()->save($set);

        try {
            $this->workout->removeExercise($this->exercise1);
        } catch (UndeletableException $e) {
            $this->assertCount(1, $this->workout->exercises);
            return;
        }
        $this->fail('Exercise with sets was removed from workout.');
    }
}
