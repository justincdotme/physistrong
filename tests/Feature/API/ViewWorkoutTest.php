<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Http\Resources\Workout as WorkoutResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewWorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $testUser1;
    protected $testUser2;

    /**
     * @test
     */
    public function authenticated_user_can_view_one_of_their_own_workouts()
    {
        $this->testUser1 = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser1,
            'name' => 'test workout',
            'created_at' => "2019-02-15 00:00:01",
            'updated_at' => "2019-02-15 00:00:01"
        ]);

        $resource = new WorkoutResource($this->workout);

        $response = $this->actingAs($this->testUser1)->json("GET", route('workouts.show', ['workout' => $this->workout->id]));

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_cannot_view_another_users_workout()
    {
        $this->testUser1 = factory(User::class)->create();
        $this->testUser2 = factory(User::class)->create([
            'first_name' => 'Justin',
            'last_name' => 'Christenson',
            'email' => 'justin@justinc.me',
            'email_verified_at' => now(),
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
        ]);
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser1,
            'name' => 'test workout',
            'created_at' => "2019-02-15 00:00:01",
            'updated_at' => "2019-02-15 00:00:01"
        ]);

        $response = $this->actingAs($this->testUser2)->json("GET", route('workouts.show', ['workout' => $this->workout->id]));

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(route('workouts.show', ['workout' => $this->workout->id], false), $responseArray['errors']['source']['pointer']);
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function authenticated_user_can_view_all_of_their_own_workouts()
    {
        $this->testUser2 = factory(User::class)->create([
            'first_name' => 'Justin',
            'last_name' => 'Christenson',
            'email' => 'justin@justinc.me',
            'email_verified_at' => now(),
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
        ]);

        factory(Workout::class, 5)->create([
            'user_id' => $this->testUser2->id,
            'name' => 'test workout'
        ]);
        $resource = WorkoutResource::collection(
            $this->testUser2->workouts()->paginate()
        );
        $resourceData = $resource->response()->getData(true);

        $response = $this->actingAs($this->testUser2)
            ->json("GET", route('workouts.index'));

        $response->assertStatus(200);
        $responseData = $response->getData(true);
        $this->assertCount(5, $responseData['data']);
        $this->assertEquals(5, $responseData['meta']['total']);
        $this->assertEquals(1, $responseData['meta']['current_page']);
        $this->assertEquals($resourceData['data'], $responseData['data']);
        $this->assertEquals(array_keys($resourceData['links']), array_keys($responseData['links']));
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_view_workouts()
    {
        $response = $this->json("GET", route('workouts.index'));

        $response->assertStatus(401);
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertEquals('401', $responseData['errors']['status']);
        $this->assertEquals('Missing token', $responseData['errors']['detail']);
        $this->assertEquals(route('workouts.index', [], false), $responseData['errors']['source']['pointer']);
    }
}
