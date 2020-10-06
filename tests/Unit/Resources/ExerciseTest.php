<?php

namespace Tests\Unit\Resources;

use Tests\TestCase;
use App\Core\Exercise;
use App\Http\Resources\Exercise as ExerciseResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExerciseTest extends TestCase
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

        $this->exercise = factory(Exercise::class)->make([
            'name' => 'squat'
        ]);

        $this->resource = new ExerciseResource($this->exercise);
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
        $this->assertArrayHasKey('links', $this->responseArray['data']);
    }

    /**
     * @test
     */
    public function it_has_correct_data()
    {
        $this->assertEquals('exercise', $this->responseArray['data']['type']);
        $this->assertEquals("{$this->exercise->id}", $this->responseArray['data']['id']);
        $this->assertEquals(
            route('exercises.show', ['exercise' => $this->exercise->id]),
            $this->responseArray['data']['links']['self']
        );
    }
}