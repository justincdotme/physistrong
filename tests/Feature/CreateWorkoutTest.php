<?php

namespace Tests\Feature;

use App\Core\User;
use App\Core\Workout;
use App\Http\Resources\Workout as WorkoutResource;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CreateWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
    }

    /**
     * @test
     */
    public function authenticated_user_can_create_their_own_workout()
    {
        $response = $this->actingAs($this->testUser)->json("POST", route('workouts.store'), [
            'name' => 'Test Workout'
        ]);
        $resource = new WorkoutResource(Workout::firstOrFail());

        $response->assertStatus(201);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function name_is_required_to_create_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("POST", route('workouts.store'), []);

        $this->assertFieldHasValidationError('name');
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_create_workout()
    {
        $response = $this->json("POST", route('workouts.store'));

        $response->assertStatus(401);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals('Missing token', $responseArray['errors']['detail']);
        $this->assertEquals(route('workouts.store', [], false), $responseArray['errors']['source']['pointer']);
    }
}
