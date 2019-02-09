<?php

namespace Tests\Feature;

use App\Core\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function authenticated_user_can_create_their_own_workout()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->json("POST", route('workouts.store'));

        $responseArray = $response->decodeResponseJson();

        $response->assertStatus(201);
        $this->assertEquals('workout', $responseArray['data']['type']);
        $this->assertEquals($user->id, $responseArray['data']['relationships']['user']['data']['id']);
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
        $this->assertEquals('401',$responseArray['errors']['status']);
        $this->assertEquals(route('workouts.store', [], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('Missing token',$responseArray['errors']['detail']);
    }
}
