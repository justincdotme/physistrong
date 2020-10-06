<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Http\Resources\Exercise as ExerciseResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewWorkoutExercisesTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $testUser;
    protected $exercise1;
    protected $exercise2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser->id
        ]);
        $this->exercise1 = factory(Exercise::class)->create();
        $this->exercise2 = factory(Exercise::class)->create();

        $this->workout->exercises()->save($this->exercise1, ['exercise_order' => 1]);
        $this->workout->exercises()->save($this->exercise2, ['exercise_order' => 2]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_view_a_workouts_exercises()
    {
        $response = $this->actingAs($this->testUser)->json("GET",
            route('workouts.exercises.index', [
                'workout' => $this->workout->id
            ])
        );
        $resource = ExerciseResource::collection($this->workout->exercises);

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_view_exercises_on_another_users_workout()
    {
        $user2 = factory(User::class)->create();

        $response = $this->actingAs($user2)->json("GET",
            route('workouts.exercises.index', [
                'workout' => $this->workout->id
            ])
        );

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(
            route('workouts.exercises.index', [
                'workout' => $this->workout->id
            ], false),
            $responseArray['errors']['source']['pointer']
        );
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_view_a_workouts_exercises()
    {
        $response = $this->json("GET",
            route('workouts.exercises.index', [
                'workout' => $this->workout->id
            ])
        );

        $response->assertStatus(401);
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertEquals('401', $responseData['errors']['status']);
        $this->assertEquals('Missing token', $responseData['errors']['detail']);
        $this->assertEquals(route('workouts.exercises.index', [
            'workout' => $this->workout->id
        ], false), $responseData['errors']['source']['pointer']);
    }
}
