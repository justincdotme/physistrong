<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Support\Carbon;
use App\Http\Resources\Workout as WorkoutResource;
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
            'date_scheduled' => Carbon::now()->toDateString()
        ]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_update_their_own_workout()
    {
        $response = $this->actingAs($this->testUser)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]), [
            'name' => 'Test Workout',
            'date_scheduled' => Carbon::now()->toDateString()
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
            'name' => 'Test Workout',
            'date_scheduled' => Carbon::now()->toDateString()
        ]);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function date_is_required_to_update_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]), [
            'name' => 'Test Workout',
        ]);

        $this->assertFieldHasValidationError('date_scheduled');
    }

    /**
     * @test
     */
    public function date_must_be_valid_date()
    {
        $this->response = $this->actingAs($this->testUser)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id]), [
            'name' => 'Test Workout',
            'date_scheduled' => 'apple'
        ]);

        $this->assertFieldHasValidationError('date_scheduled');
    }


    /**
     * @test
     */
    public function name_is_required_to_update_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("PUT", route('workouts.update',
            ['workout' => $this->workout->id, ]), [
            'date_scheduled' => Carbon::now()->toDateString()
        ]);

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
