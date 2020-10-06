<?php

namespace Tests\Unit\Resources;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Http\Resources\ExerciseSet as WorkoutSetResource;

class ExerciseSetTest extends TestCase
{
    use DatabaseMigrations;

    protected $set;
    protected $date;
    protected $workout;
    protected $testUser;
    protected $resource;
    protected $exercise;
    protected $responseArray;

    protected function setUp(): void
    {
        parent::setUp();
        $this->date = Carbon::now()->toDateTimeString();
        $this->testUser = factory(User::class)->make([
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
        $this->resource = new WorkoutSetResource($this->set);
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
        $this->assertEquals('set', $this->responseArray['data']['type']);
        $this->assertEquals("{$this->set->id}", $this->responseArray['data']['id']);
        $this->assertEquals($this->set->count, $this->responseArray['data']['attributes']['count']);
        $this->assertEquals($this->set->weight, $this->responseArray['data']['attributes']['weight']);
        $this->assertEquals($this->set->set_order, $this->responseArray['data']['attributes']['set_order']);
        $this->assertEquals($this->set->exercise_id, $this->responseArray['data']['attributes']['exercise_id']);
        $this->assertEquals(
            route('sets.show', [
                'set' => $this->set->id
            ]),
            $this->responseArray['data']['links']['self']
        );
    }
}