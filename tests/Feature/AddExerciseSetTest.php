<?php

namespace Tests\Feature;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Http\Resources\ExerciseSet;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AddExerciseSetTest extends TestCase
{
    use DatabaseMigrations;

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
    }
    /**
     * @test
     */
    public function authenticated_user_can_add_set_to_their_own_workout()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10,
            'set_order' => 1
        ]);

        $resource = new ExerciseSet(Set::firstOrFail());

        $this->response->assertResource($resource);
        $this->response->assertStatus(201);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_add_exercise_to_workout()
    {
        $this->response = $this->json("POST", route('workouts.exercises.sets.store', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10
        ]));


        $this->response->assertStatus(401);
    }

    /**
     * @test
     */
    public function user_cannot_add_exercise_to_another_users_workout()
    {
        $user2 = factory(User::class)->create();
        $workout2 = factory(Workout::class)->create([
            'user_id' => $user2->id
        ]);
        $this->response = $this->addExerciseSet([
            'workout' =>$workout2,
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10
        ]);


        $this->response->assertStatus(403);
    }

    /**
     * @test
     */
    public function workout_is_required_for_adding_a_set()
    {
        $this->response = $this->addExerciseSet([
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->response->assertStatus(404);
    }

    /**
     * @test
     */
    public function exercise_is_required_for_adding_a_set()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->response->assertStatus(404);
    }

    /**
     * @test
     */
    public function set_order_is_required_for_adding_a_set()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
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
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
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
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
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
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
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
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
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
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
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
    protected function addExerciseSet($params)
    {
        return $this->actingAs($this->testUser)->json("POST", route('workouts.exercises.sets.store', $params));
    }
}
