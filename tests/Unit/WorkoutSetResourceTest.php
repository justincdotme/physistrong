<?php

namespace Tests\Unit;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Support\Carbon;
use App\Http\Resources\WorkoutSetResource;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutSetResourceTest extends TestCase
{
    use DatabaseMigrations;

    protected $set;
    protected $date;
    protected $workout;
    protected $testUser;
    protected $resource;
    protected $exercise;
    protected $responseArray;

    public function setUp()
    {
        parent::setUp();
        $this->date = Carbon::now()->toDateTimeString();
        $this->testUser = factory(User::class)->create([
            'id' => 1
        ]);
        $this->workout = factory(Workout::class)->create([
            'id' => 1,
            'user_id' => $this->testUser->id,
            'created_at' => $this->date
        ]);
        $this->exercise = factory(Exercise::class)->create([
            'name' => 'squat'
        ]);
        $this->set = factory(Set::class)->create([
            'workout_id' => $this->workout->id,
            'exercise_id' => $this->exercise->id,
            'weight' => 100,
            'count' => 10,
        ]);
        $this->resource = new WorkoutSetResource($this->set);
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
    public function it_has_type_set()
    {
        $this->assertEquals('set', $this->responseArray['data']['type']);
    }

    /**
     * @test
     */
    public function it_has_correct_id()
    {
        $this->assertEquals("{$this->set->id}", $this->responseArray['data']['id']);
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
            route('workouts.sets.show', ['workout' => $this->workout->id, 'set' => $this->set->id]),
            $this->responseArray['data']['links']['self']
        );
    }
}