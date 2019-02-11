<?php

namespace Tests\Unit;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Support\Carbon;
use App\Http\Resources\WorkoutResource;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutResourceTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;
    protected $workout;
    protected $resource;
    protected $responseArray;
    protected $date;

    public function setUp()
    {
        parent::setUp();
        $this->date = Carbon::now()->toDateTimeString();
        $this->testUser = factory(User::class)->create([
            'id' => 1
        ]);
        $this->workout = factory(Workout::class)->make([
            'id' => 1,
            'user_id' => $this->testUser->id,
            'created_at' => $this->date
        ]);
        $this->resource = new WorkoutResource($this->workout);
        $this->responseArray = json_decode($this->resource->response()->getContent(), true);
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
    public function it_has_correct_date()
    {
        $this->assertEquals($this->date, $this->responseArray['data']['attributes']['date']);
    }

    /**
     * @test
     */
    public function it_has_link_to_self()
    {
        $this->assertEquals(
            route('workouts.show', ['workout' => $this->workout->id]),
            $this->responseArray['data']['links']['self']
        );
    }
}
