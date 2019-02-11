<?php

namespace Tests\Feature;

use App\Core\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewUserTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function authenticated_user_can_view_their_user_object()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->json("GET", route('users.show', ['user' => $user->id]));

        $response->assertStatus(200);
        $responseArray = $response->decodeResponseJson();
        $this->assertEquals('user', $responseArray['data']['type']);
        $this->assertEquals($user->id, $responseArray['data']['id']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_view_any_users()
    {
        $user = factory(User::class)->create();

        $response = $this->json("GET", route('users.show', ['user' => $user->id]));

        $response->assertStatus(401);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals(route('users.show', ['user' => $user->id], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('Missing token', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function authenticated_user_cannot_view_another_user()
    {
        $user1 = factory(User::class)->create([
            'id' => 1
        ]);
        $user2 = factory(User::class)->create([
            'id' => 2
        ]);

        $response = $this->actingAs($user2)->json("GET", route('users.show', ['user' => $user1->id]));

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(route('users.show', ['user' => $user1->id], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }
}
