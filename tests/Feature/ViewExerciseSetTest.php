<?php

namespace Tests\Feature;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Http\Resources\ExerciseSet;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewExerciseSetTest extends TestCase
{
    use DatabaseMigrations;

    protected $sets;
    protected $testUser;
    protected $exercise;
    protected $workout;

    public function setUp()
    {
        parent::setUp();
        $this->workout = factory(Workout::class)->create();
        $this->testUser = factory(User::class)->create();
        $this->workout->user()->associate($this->testUser);

        $this->exercise = factory(Exercise::class)->create([
            'name' => 'Bench Press',
            'id' => 1
        ]);

        $this->sets = collect([ //Create the sets out of order
            factory(Set::class)->make([
                'exercise_id' => $this->exercise->id,
                'set_order' => 3
            ]),
            factory(Set::class)->make([
                'exercise_id' => $this->exercise->id,
                'set_order' => 2
            ]),
            factory(Set::class)->make([
                'exercise_id' => $this->exercise->id,
                'set_order' => 1
            ]),
        ]);

        $this->workout->sets()->saveMany($this->sets);
    }

    /**
     * @test
     */
    public function index_route_returns_correct_format()
    {
        $resource = ExerciseSet::collection($this->workout->sets()->ofExercise($this->exercise)->get());

        $response = $this->actingAs($this->testUser)->json("GET", route('workouts.exercises.sets.index', ['workout' => $this->workout->id, 'exercise' => $this->exercise->id]));

        $response->assertResource($resource);
    }
}
