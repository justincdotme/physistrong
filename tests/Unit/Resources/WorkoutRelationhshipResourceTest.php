<?php

namespace Tests\Unit\Resources;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Http\Resources\WorkoutRelationship as WorkoutRelationshipResource;

class WorkoutRelationhshipResourceTest extends TestCase
{
    use DatabaseMigrations;

    protected $workout;
    protected $testUser;
    protected $exercise;
    protected $resource;
    protected $responseArray;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->make([
            'id' => 1
        ]);
        $this->workout = factory(Workout::class)->make([
            'id' => 1,
            'name' => 'test workout'
        ]);

        $this->workout->setRelation('user', $this->testUser);

        $this->resource = new WorkoutRelationshipResource($this->workout);
        $this->responseArray = $this->resource->response()->getData(true);
    }

    /**
     * @test
     */
    public function it_returns_correct_structure()
    {
        $this->assertArrayHasKey('user', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']['user']);
        $this->assertArrayHasKey('self', $this->responseArray['data']['user']['links']);

        $this->assertArrayHasKey('sets', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']['sets']);
        $this->assertArrayHasKey('self', $this->responseArray['data']['sets']['links']);

        $this->assertArrayHasKey('exercises', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']['exercises']);
        $this->assertArrayHasKey('self', $this->responseArray['data']['exercises']['links']);
    }

    /**
     * @test
     */
    public function it_returns_correct_data()
    {
        $this->assertEquals(
            route('user.show', ['user' => $this->testUser->id]),
            $this->responseArray['data']['user']['links']['self']
        );
        $this->assertEquals(
            route('workouts.sets.index', ['workout' => $this->workout->id]),
            $this->responseArray['data']['sets']['links']['self']
        );
        $this->assertEquals(
            route('workouts.exercises.index', ['workout' => $this->workout->id]),
            $this->responseArray['data']['exercises']['links']['self']
        );
    }
}
