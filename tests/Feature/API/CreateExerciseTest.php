<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Exercise;
use ExerciseTypesTableSeeder;
use App\Http\Resources\Exercise as ExerciseResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CreateExerciseTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
    }

    /**
     * @test
     */
    public function authenticated_user_can_create_an_exercise()
    {
        $this->seed(ExerciseTypesTableSeeder::class);
        $this->response = $this->createExercise([
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 1
        ]);

        $exercises = Exercise::get();
        $resource = new ExerciseResource($exercises->first());

        $this->assertCount(1, $exercises);
        $this->response->assertStatus(201);
        $this->response->assertResource($resource);
        $this->assertEquals($this->testUser->id, $exercises->first()->user_id);
        $this->assertEquals('Close Grip Bench Press', $exercises->first()->name);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_create_an_exercise()
    {
        $this->response = $this->json("POST", route('exercises.store'), [
            'name' => 1,
            'exercise_type' => 1
        ]);

        $this->response->assertStatus(401);
        $responseData = $this->response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertEquals('401', $responseData['errors']['status']);
        $this->assertEquals('Missing token', $responseData['errors']['detail']);
        $this->assertEquals(route('exercises.store', [], false), $responseData['errors']['source']['pointer']);
    }

    /**
     * @test
     */
    public function exercise_name_is_required()
    {
        $this->response = $this->createExercise([
            'exercise_type' => 1
        ]);

        $this->assertFieldHasValidationError('name');
    }

    /**
     * @test
     */
    public function exercise_type_is_required()
    {
        $this->response = $this->createExercise([
            'name' => 'Close Grip Bench Press',
        ]);

        $this->assertFieldHasValidationError('exercise_type');
    }

    /**
     * @test
     */
    public function exercise_type_must_be_an_integer()
    {
        $this->response = $this->createExercise([
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 'apple'
        ]);

        $this->assertFieldHasValidationError('exercise_type');
    }

    /**
     * @test
     */
    public function existing_exercise_type_is_required()
    {
        $this->response = $this->createExercise([
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 99
        ]);

        $this->assertFieldHasValidationError('exercise_type');
    }

    /**
     * @test
     */
    public function exercise_name_must_be_unique_to_user()
    {
        Exercise::create([
            'name' => 'Close Grip Bench Press',
            'user_id' => $this->testUser->id,
            'exercise_type_id' => 1
        ]);

        $this->response = $this->createExercise([
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 1
        ]);

        $this->assertFieldHasValidationError('name');
    }

    /**
     * Helper method to create an exercise via HTTP POST.
     *
     * @param $params
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function createExercise($params)
    {
        return $this->actingAs($this->testUser)->json("POST", route('exercises.store'), $params);
    }
}
