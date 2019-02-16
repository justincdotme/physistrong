<?php

namespace Tests\Feature;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Http\Resources\ExerciseSet;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EditExerciseSetTest extends TestCase
{
    use DatabaseMigrations;

    protected $set;
    protected $testUser;
    protected $workout;
    protected $exercise;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser->id
        ]);
        $this->exercise = factory(Exercise::class)->create([
            'name' => 'squat'
        ]);
        $this->set = factory(Set::class)->create([
            'exercise_id' => $this->exercise->id,
            'workout_id' => $this->workout->id,
            'weight' => 10,
            'count' => 5
        ]);
    }
    /**
     * @test
     */
    public function authenticated_user_can_update_their_own_set()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 99,
            'count' => 11,
            'set_order' => 1
        ]);
        $this->set = $this->set->fresh();
        $resource = new ExerciseSet($this->set);

        $this->response->assertStatus(200);
        $this->response->assertResource($resource);
        $this->assertEquals(99, $this->set->weight);
        $this->assertEquals(11, $this->set->count);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_update_exercise_set()
    {
        $this->response = $this->json("POST", route('workouts.exercises.sets.store', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 10
        ]));

        $this->response->assertStatus(401);
    }

    /**
     * @test
     */
    public function user_cannot_update_exercises_on_another_users_workout()
    {
        $user2 = factory(User::class)->create();
        $workout2 = factory(Workout::class)->create([
            'user_id' => $user2->id
        ]);
        $this->response = $this->updateExerciseSet([
            'workout' =>$workout2,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->response->assertStatus(403);
    }

    /**
     * @test
     */
    public function workout_is_required_for_updating_a_set()
    {
        $this->response = $this->updateExerciseSet([
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->response->assertStatus(404);
    }

    /**
     * @test
     */
    public function exercise_is_required_for_updating_a_set()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->response->assertStatus(404);
    }

    /**
     * @test
     */
    public function set_is_required_for_updating_a_set()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->response->assertStatus(404);
    }

    /**
     * @test
     */
    public function set_order_is_required_for_updating_a_set()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->assertFieldHasValidationError('set_order');
    }

    /**
     * @test
     */
    public function set_order_must_be_an_integer()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 10,
            'set_order' => 'apple'
        ]);

        $this->assertFieldHasValidationError('set_order');
    }

    /**
     * @test
     */
    public function set_order_must_be_greater_than_0()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 10,
            'set_order' => 0
        ]);

        $this->assertFieldHasValidationError('set_order');
    }

    /**
     * @test
     */
    public function weight_must_be_an_integer()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 'banana',
            'count' => 10
        ]);

        $this->assertFieldHasValidationError('weight');
    }

    /**
     * @test
     */
    public function count_must_be_an_integer()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 'banana'
        ]);

        $this->assertFieldHasValidationError('count');
    }

    /**
     * @test
     */
    public function count_must_be_greater_than_0()
    {
        $this->response = $this->updateExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->set->id,
            'weight' => 120,
            'count' => 0
        ]);

        $this->assertFieldHasValidationError('count');
    }

    /**
     * Helper method for creating exercis set via HTTP request.
     *
     * @param $params
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function updateExerciseSet($params)
    {
        return $this->actingAs($this->testUser)->json("PUT", route('workouts.exercises.sets.update', $params));
    }
}
