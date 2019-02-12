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
        $this->testUser = factory(User::class)->create([
            'id' => 1,
            'first_name' => 'Justin',
            'last_name' => 'Christenson'
        ]);
        $this->workouts = factory(Workout::class, 2)->create([
            'user_id' => $this->testUser->id
        ]);

        $this->resource = new UserResource($this->testUser);

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
    public function it_has_type_user()
    {
         $this->assertEquals('user', $this->responseArray['data']['type']);
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
    public function it_has_correct_name()
    {
        $this->assertEquals('Justin', $this->responseArray['data']['attributes']['first_name']);
        $this->assertEquals('Christenson', $this->responseArray['data']['attributes']['last_name']);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $this->assertArrayHasKey('workouts', $this->responseArray['data']['relationships']);
        $this->assertEquals(
            route('workouts.index'),
            $this->responseArray['data']['relationships']['workouts']['links']['related']
        );
    }
}
