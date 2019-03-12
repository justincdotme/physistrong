<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Http\Resources\Workout as WorkoutResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AddExerciseToWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $exercise;
    protected $testUser;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser->id
        ]);
        $this->exercise = factory(Exercise::class)->create();
    }

    /**
     * @test
     */
    public function authenticated_user_can_add_exercise_to_workout()
    {
        $response = $this->actingAs($this->testUser)->json("POST",
            route('workouts.exercises.store', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id
            ]), [
                'exercise_order' => 1
            ]
        );
        $resource = new WorkoutResource(Workout::firstOrFail());

        $response->assertStatus(201);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_add_exercise_to_another_users_workout()
    {
        $user2 = factory(User::class)->create();

        $response = $this->actingAs($user2)->json("POST",
            route('workouts.exercises.store', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id
            ]), [
                'exercise_order' => 1
            ]
        );

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(
            route('workouts.exercises.store', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id
            ], false),
            $responseArray['errors']['source']['pointer']
        );
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_add_exercise_to_workout()
    {
        $response = $this->json("POST",
            route('workouts.exercises.store', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id
            ]), [
                'exercise_order' => 1
            ]
        );

        $response->assertStatus(401);
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertEquals('401', $responseData['errors']['status']);
        $this->assertEquals('Missing token', $responseData['errors']['detail']);
        $this->assertEquals(route('workouts.exercises.store', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id
        ], false), $responseData['errors']['source']['pointer']);
    }

    /**
     * @test
     */
    public function exercise_order_is_required_to_add_exercise_to_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("POST",
            route('workouts.exercises.store', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id
            ])
        );

        $this->assertFieldHasValidationError('exercise_order');
    }

    /**
     * @test
     */
    public function exercise_order_is_must_be_integer_to_add_exercise_to_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("POST",
            route('workouts.exercises.store', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id
            ], [
                'exercise_order' => 'apple'
            ])
        );

        $this->assertFieldHasValidationError('exercise_order');
    }
}
