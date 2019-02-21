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
    public function authenticated_user_can_view_their_own_exercise_sets()
    {
        $resource = ExerciseSet::collection($this->workout->sets()->ofExercise($this->exercise)->get());

        $response = $this->actingAs($this->testUser)->json("GET", route('workouts.exercises.sets.index', ['workout' => $this->workout->id, 'exercise' => $this->exercise->id]));

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_can_view_their_own_exercise_set()
    {
        $resource = new ExerciseSet($this->workout->sets()->first());

        $response = $this->actingAs($this->testUser)->json("GET", route(
                'sets.show', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id,
                'set' => $this->workout->sets()->first()->id
            ])
        );

        $response->assertStatus(200);
        $response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_cannot_view_another_users_set()
    {
        $user2 = factory(User::Class)->create();
        $response = $this->actingAs($user2)->json("GET", route(
                'sets.show', [
                'workout' => $this->workout->id,
                'exercise' => $this->exercise->id,
                'set' => $this->workout->sets()->first()->id
            ])
        );

        $response->assertStatus(403);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_view_users_set()
    {
        $response = $this->json("GET", route(
            'sets.show', [
            'workout' => $this->workout->id,
            'exercise' => $this->exercise->id,
            'set' => $this->workout->sets()->first()->id
        ]));

        $response->assertStatus(401);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals('Missing token', $responseArray['errors']['detail']);
    }
}
