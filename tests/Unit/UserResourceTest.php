<?php

namespace Tests\Unit;

use App\Core\User;
use App\Core\Workout;
use App\Http\Resources\UserResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserResourceTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function it_returns_correct_structure()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);

        $resource = new UserResource($user);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertArrayHasKey('type', $responseArray['data']);
        $this->assertArrayHasKey('id', $responseArray['data']);
        $this->assertArrayHasKey('attributes', $responseArray['data']);
        $this->assertArrayHasKey('relationships', $responseArray['data']);
        $this->assertArrayHasKey('links', $responseArray['data']);
    }

    /**
     * @test
     */
    public function it_has_type_user()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);

        $resource = new UserResource($user);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertEquals('user', $responseArray['data']['type']);
    }

    /**
     * @test
     */
    public function it_has_correct_id()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);

        $resource = new UserResource($user);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertEquals('1', $responseArray['data']['id']);
    }

    /**
     * @test
     */
    public function it_has_correct_name()
    {
        $user = factory(User::class)->create([
            'id' => 1,
            'first_name' => 'Justin',
            'last_name' => 'Christenson'
        ]);

        $resource = new UserResource($user);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertEquals('Justin', $responseArray['data']['attributes']['first_name']);
        $this->assertEquals('Christenson', $responseArray['data']['attributes']['last_name']);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);
        $workouts = factory(Workout::class, 2)->create([
            'user_id' => $user->id
        ]);
        $resource = new UserResource($user);

        $responseArray = json_decode($resource->response()->getContent(), true);
        $this->assertArrayHasKey('workouts', $responseArray['data']['relationships']);
        $this->assertEquals(
            route('workouts.index'),
            $responseArray['data']['relationships']['workouts']['links']['related']
        );
    }
}
