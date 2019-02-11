<?php

namespace Tests\Feature;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class addExerciseSetTest extends TestCase
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
        $this->withoutExceptionHandling();

        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'weight' => 120,
            'count' => 10
        ]);

        $responseArray = $this->response->decodeResponseJson();
        $this->response->assertStatus(201);
        $this->assertEquals('workout', $responseArray['data']['type']);
        $this->assertEquals($this->testUser->id, $responseArray['data']['relationships']['user']['data']['id']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_add_exercise_to_workout()
    {
        $this->response = $this->json("POST", route('workouts.set.store', [
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
    public function exercise_is_required_for_adding_a_set()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'weight' => 120,
            'count' => 10
        ]);

        $this->assertFieldHasValidationError('exercise');
    }

    /**
     * @test
     */
    public function exercise_must_be_an_integer()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => 'banana',
            'weight' => 120,
            'count' => 10
        ]);

        $this->assertFieldHasValidationError('exercise');
    }

    /**
     * @test
     */
    public function weight_is_required_for_adding_a_set()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'count' => 10
        ]);

        $this->assertFieldHasValidationError('weight');
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
    public function count_is_required_for_adding_a_set()
    {
        $this->response = $this->addExerciseSet([
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'weight' => 120
        ]);

        $this->assertFieldHasValidationError('count');
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
     * @test
     */
    public function user_can_add_multiple_sets_of_same_exercise()
    {
        $weight = 120;
        $count = 10;
        $set1 = Set::forWorkout($this->workout, $this->exercise, $weight, $count);
        $set2 = Set::forWorkout($this->workout, $this->exercise, $weight, $count);

        $this->assertCount(2, $this->workout->sets()->get());
    }

    /**
     * Helper method for creating exercis set via HTTP request.
     *
     * @param $params
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function addExerciseSet($params)
    {
        return $this->actingAs($this->testUser)->json("POST", route('workouts.set.store', $params));
    }
}
