<?php

namespace Tests\Unit\Resources;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Http\Resources\User as UserResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserTest extends TestCase
{
    use DatabaseMigrations;

    protected $date;
    protected $resource;
    protected $testUser;
    protected $workouts;
    protected $responseArray;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->make([
            'id' => 1,
            'first_name' => 'Justin',
            'last_name' => 'Christenson'
        ]);
        $this->workouts = factory(Workout::class, 2)->create([
            'user_id' => $this->testUser->id
        ]);

        $this->resource = new UserResource($this->testUser);

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
    public function it_has_correct_data()
    {

        $this->assertEquals('1', $this->responseArray['data']['id']);
        $this->assertEquals('user', $this->responseArray['data']['type']);
        $this->assertArrayHasKey('workouts', $this->responseArray['data']['relationships']);
        $this->assertEquals('Justin', $this->responseArray['data']['attributes']['first_name']);
        $this->assertEquals('Christenson', $this->responseArray['data']['attributes']['last_name']);
        $this->assertEquals(
            route('workouts.index'),
            $this->responseArray['data']['relationships']['workouts']['links']['self']
        );
    }
}
