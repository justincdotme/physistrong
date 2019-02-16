<?php

namespace Tests\Unit\Resources;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Http\Resources\WorkoutSetRelationship as WorkoutSetRelationshipResource;

class WorkoutSetRelationshipTest extends TestCase
{
    use DatabaseMigrations;

    protected $set;
    protected $date;
    protected $workout;
    protected $testUser;
    protected $exercise;
    protected $resource;
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
            'set_order' => 1
        ]);

        $this->resource = new WorkoutSetRelationshipResource($this->workout->sets()->first());

        $this->responseArray = $this->resource->response()->getData(true);
    }

    /**
     * @test
     */
    public function it_returns_correct_structure()
    {
        $this->assertArrayHasKey('workout', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']['workout']);
        $this->assertArrayHasKey('data', $this->responseArray['data']['workout']);
        $this->assertArrayHasKey('self', $this->responseArray['data']['workout']['links']);

        $this->assertArrayHasKey('exercise', $this->responseArray['data']);
        $this->assertArrayHasKey('links', $this->responseArray['data']['exercise']);
        $this->assertArrayHasKey('data', $this->responseArray['data']['exercise']);
        $this->assertArrayHasKey('self', $this->responseArray['data']['exercise']['links']);
    }

    /**
     * @test
     */
    public function it_returns_correct_data()
    {
        $this->assertEquals(
            route('workouts.show', ['workout' => $this->workout->id]),
            $this->responseArray['data']['workout']['links']['self']
        );
        $this->assertEquals(
            route('exercises.show', ['exercise' => $this->exercise->id]),
            $this->responseArray['data']['exercise']['links']['self']
        );
    }
}
