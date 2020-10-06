<?php

namespace Tests\Feature\API;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RemoveExerciseFromWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $exercise;
    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser->id
        ]);
        $this->exercise = factory(Exercise::class)->create();
        $this->workout->exercises()->save( $this->exercise, ['exercise_order' => 1]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_remove_exercise_from_their_own_workout()
    {
        $response = $this->actingAs($this->testUser)->json("DELETE", route('workouts.exercises.destroy', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id
        ]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('exercise_workout', [
            'exercise_id' => $this->exercise->id,
            'workout_id' => $this->workout->id
        ]);
    }

    /**
     * @test
     */
    public function only_exercises_without_corresponding_sets_can_be_removed()
    {
        $set = factory(Set::class)->create([
            'workout_id' => $this->workout->id,
            'exercise_id' => $this->exercise->id
        ]);

        $response = $this->actingAs($this->testUser)->json("DELETE", route('workouts.exercises.destroy', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id
        ]));

        $response->assertStatus(409);
        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $this->exercise->id,
            'workout_id' => $this->workout->id
        ]);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('409', $responseArray['errors']['status']);
        $this->assertEquals('The resource could not be deleted due to dependency conflict.', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_remove_exercise_from_another_users_workout()
    {
        $user2 = factory(User::class)->create();
        $response = $this->actingAs($user2)->json("DELETE", route('workouts.exercises.destroy', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id
        ]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $this->exercise->id,
            'workout_id' => $this->workout->id
        ]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_remove_exercise_from_workout()
    {
        $response = $this->json("DELETE", route('workouts.exercises.destroy', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id
        ]));

        $response->assertStatus(401);
        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $this->exercise->id,
            'workout_id' => $this->workout->id
        ]);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals('Missing token', $responseArray['errors']['detail']);
        $this->assertEquals(route('workouts.exercises.destroy', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id
        ], false), $responseArray['errors']['source']['pointer']);
    }
}
