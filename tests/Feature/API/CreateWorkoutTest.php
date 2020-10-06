<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Support\Carbon;
use App\Http\Resources\Workout as WorkoutResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CreateWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;

    protected function setUp(): void
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
            'name' => 'Zombie Workout',
            'date_scheduled' => Carbon::now()->toDateString()
        ]);

        $response->assertStatus(201);
        $response->assertResource(
            new WorkoutResource(Workout::firstOrFail())
        );
    }

    /**
     * @test
     */
    public function date_is_required_to_create_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("POST", route('workouts.store'), [
            'name' => 'Zombie Workout'
        ]);

        $this->assertFieldHasValidationError('date_scheduled');
    }

    /**
     * @test
     */
    public function date_must_be_valid_date()
    {
        $this->response = $this->actingAs($this->testUser)->json("POST", route('workouts.store'), [
            'name' => 'Zombie Workout',
            'date_scheduled' => 'apple'
        ]);

        $this->assertFieldHasValidationError('date_scheduled');
    }


    /**
     * @test
     */
    public function name_is_required_to_create_workout()
    {
        $this->response = $this->actingAs($this->testUser)->json("POST", route('workouts.store'), [
            'date_scheduled' => Carbon::now()->toDateString()
        ]);

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
