<?php

namespace Tests\Unit;

use App\Core\User;
use App\Core\Workout;
use App\Http\Resources\WorkoutResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkoutResourceTest extends TestCase
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
        $workout = factory(Workout::class)->make([
            'user_id' => $user->id
        ]);
        $resource = new WorkoutResource($workout);

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
    public function it_has_type_workout()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);
        $workout = factory(Workout::class)->make([
            'user_id' => $user->id
        ]);
        $resource = new WorkoutResource($workout);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertEquals('workout', $responseArray['data']['type']);
    }

    /**
     * @test
     */
    public function it_has_correct_id()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);
        $workout = factory(Workout::class)->make([
            'id' => 1,
            'user_id' => 1
        ]);
        $resource = new WorkoutResource($workout);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertEquals('1', $responseArray['data']['id']);
    }

    /**
     * @test
     */
    public function it_has_correct_date()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);
        $workout = factory(Workout::class)->make([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->toDateTimeString()
        ]);
        $resource = new WorkoutResource($workout);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertEquals(Carbon::now()->toDateTimeString(), $responseArray['data']['attributes']['date']);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_user()
    {
        $user = factory(User::class)->create([
            'id' => 1
        ]);
        $workout = factory(Workout::class)->make([
            'user_id' => $user->id
        ]);
        $resource = new WorkoutResource($workout);

        $responseArray = json_decode($resource->response()->getContent(), true);

        $this->assertArrayHasKey('user', $responseArray['data']['relationships']);
        $this->assertEquals(
            route('users.show', ['user' => 1]),
            $responseArray['data']['relationships']['user']['links']['self']
        );
    }
}