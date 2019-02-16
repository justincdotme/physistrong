<?php

namespace Tests\Feature;

use App\Core\User;
use App\Core\Workout;
use App\Http\Resources\Workout as WorkoutResource;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UpdateWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;
    protected $workout;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'id' => 2,
            'user_id' => $this->testUser->id,
            'name' => 'test workout',
            'name' => 'workout 2',
        ]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_update_their_own_workout()
    {
        $response = $this->actingAs($this->testUser)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]), [
            'name' => 'Test Workout'
        ]);
        $resource = new WorkoutResource(Workout::find($this->workout->id));

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_update_another_users_workout()
    {
        $user2 = factory(User::class)->create();

        $response = $this->actingAs($user2)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]), [
            'name' => 'Test Workout'
        ]);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function name_is_required_to_update_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]), []);

        $this->assertFieldHasValidationError('name');
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_update_workout()
    {
        $response = $this->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]));

        $response->assertStatus(401);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals('Missing token', $responseArray['errors']['detail']);
        $this->assertEquals(
            route('workouts.update', ['workout' => $this->workout->id], false),
            $responseArray['errors']['source']['pointer']
        );
    }
}
