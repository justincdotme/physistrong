<?php

namespace Tests\Unit\Resources;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Http\Resources\UserRelationship;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserRelationshipTest extends TestCase
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
        $this->testUser->setRelation('workouts', collect([$this->workout]));

        $this->resource = new UserRelationship($this->testUser );

        $this->responseArray = $this->resource->response()->getData(true);
    }

    /**
     * @test
     */
    public function it_returns_correct_structure()
    {
        $this->assertArrayHasKey('workouts', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']['workouts']);
        $this->assertArrayHasKey('self', $this->responseArray['data']['workouts']['links']);
    }

    /**
     * @test
     */
    public function it_returns_correct_data()
    {
        $this->assertEquals(
            route('workouts.index'),
            $this->responseArray['data']['workouts']['links']['self']
        );
    }
}
