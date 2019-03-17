<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Http\Resources\User as UserResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewUserTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function it_can_fetch_authenticated_user_by_token()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->json("GET", route('user.from-token'));

        $resource = new UserResource($user);

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_can_view_their_user_object()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->json("GET", route('user.show', ['user' => $user->id]));
        $resource = new UserResource($user);

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_view_any_users()
    {
        $user = factory(User::class)->create();

        $response = $this->json("GET", route('user.show', ['user' => $user->id]));

        $response->assertStatus(401);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals(route('user.show', ['user' => $user->id], false), $responseArray['errors']['source']['pointer']);
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

        $response = $this->actingAs($user2)->json("GET", route('user.show', ['user' => $user1->id]));

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(route('user.show', ['user' => $user1->id], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }
}
