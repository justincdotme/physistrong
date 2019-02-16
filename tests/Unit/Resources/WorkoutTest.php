<?php

namespace Tests\Unit\Resources;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Support\Carbon;
use App\Http\Resources\Workout as WorkoutResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $date;
    protected $workout;
    protected $resource;
    protected $testUser;
    protected $responseArray;

    public function setUp()
    {
        parent::setUp();
        $this->date = Carbon::now()->toDateTimeString();
        $this->testUser = factory(User::class)->create([
            'id' => 1
        ]);
        $this->workout = factory(Workout::class)->make([
            'id' => 1,
            'name' => 'Test Workout',
            'user_id' => $this->testUser->id,
            'created_at' => $this->date
        ]);
        $this->resource = new WorkoutResource($this->workout);
        $this->responseArray = $this->resource->response()->getData(true);
    }

    /**
     * @test
     */
    public function it_returns_correct_structure()
    {
        $this->assertArrayHasKey('type', $this->responseArray['data']);
        $this->assertArrayHasKey('id', $this->responseArray['data']);
        $this->assertArrayHasKey('attributes', $this->responseArray['data']);
        $this->assertArrayHasKey('relationships', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']);
    }

    /**
     * @test
     */
    public function it_has_correct_name()
    {
        $this->assertEquals($this->workout->name, $this->responseArray['data']['attributes']['name']);
    }

    /**
     * @test
     */
    public function it_has_type_workout()
    {
        $this->assertEquals('workout', $this->responseArray['data']['type']);
    }

    /**
     * @test
     */
    public function it_has_correct_id()
    {
        $this->assertEquals('1', $this->responseArray['data']['id']);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_user()
    {
        $this->assertArrayHasKey('user', $this->responseArray['data']['relationships']);
        $this->assertEquals(
            route('users.show', ['user' => 1]),
            $this->responseArray['data']['relationships']['user']['links']['self']
        );
    }

    /**
     * @test
     */
    public function it_has_relationship_to_sets()
    {
        $this->assertEquals(
            route('workouts.show', ['workout' => $this->workout->id]),
            $this->responseArray['data']['links']['self']

        );
    }
}
