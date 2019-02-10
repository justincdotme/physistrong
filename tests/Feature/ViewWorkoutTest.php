<?php

namespace Tests\Feature;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function authenticated_user_can_view_one_of_their_own_workouts()
    {
        $user = factory(User::class)->create();
        $workout = factory(Workout::class)->create([
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)->json("GET", route('workouts.show', ['workout' => $workout->id]));

        $responseArray = $response->decodeResponseJson();
        $response->assertStatus(200);
        $this->assertNotEmpty($responseArray['data']);
        $this->assertEquals('workout', $responseArray['data']['type']);
        $this->assertEquals($user->id, $responseArray['data']['relationships']['user']['data']['id']);
    }

    /**
     * @test
     */
    public function authenticated_user_cannot_view_another_users_workout()
    {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();
        $workout = factory(Workout::class)->create([
            'user_id' => $user1->id
        ]);

        $response = $this->actingAs($user2)->json("GET", route('workouts.show', ['workout' => $workout->id]));

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403',$responseArray['errors']['status']);
        $this->assertEquals(route('workouts.show', ['workout' => $workout->id], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('This action is unauthorized',$responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function authenticated_user_can_view_all_of_their_own_workouts()
    {
        $user = factory(User::class)->create();
        factory(Workout::class, 5)->create([
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)->json("GET", route('workouts.index'));

        $responseArray = $response->decodeResponseJson();
        $response->assertStatus(200);
        $this->assertNotEmpty($responseArray['data']);
        $this->assertCount(5, $responseArray['data']);
        $this->assertArrayHasKey('links', $responseArray);
        $this->assertArrayHasKey('first', $responseArray['links']);
        $this->assertArrayHasKey('last', $responseArray['links']);
        $this->assertArrayHasKey('prev', $responseArray['links']);
        $this->assertArrayHasKey('next', $responseArray['links']);
        $this->assertArrayHasKey('meta', $responseArray);
        $this->assertEquals(1, $responseArray['meta']['current_page']);
        $this->assertEquals(5, $responseArray['meta']['total']);
        $this->assertArrayHasKey('current_page', $responseArray['meta']);
        $this->assertArrayHasKey('per_page', $responseArray['meta']);
        $this->assertArrayHasKey('total', $responseArray['meta']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_view_workouts()
    {
        $response = $this->json("GET", route('workouts.index'));

        $response->assertStatus(401);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401',$responseArray['errors']['status']);
        $this->assertEquals(route('workouts.index', [], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('Missing token',$responseArray['errors']['detail']);
    }
}
