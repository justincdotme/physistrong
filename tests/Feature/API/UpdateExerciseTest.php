<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Exercise;
use ExerciseTypesTableSeeder;
use App\Http\Resources\Exercise as ExerciseResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UpdateExerciseTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;
    protected $exercise;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExerciseTypesTableSeeder::class);
        $this->testUser = factory(User::class)->create();
        $this->exercise = factory(Exercise::class)->create([
            'user_id' => $this->testUser->id
        ]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_update_an_exercise()
    {
        $this->response = $this->updateExercise([
            'exercise' => $this->exercise->id
        ], [
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 1
        ]);

        $exercises = Exercise::get();
        $resource = new ExerciseResource($exercises->first());

        $this->assertCount(1, $exercises);
        $this->response->assertStatus(200);
        $this->response->assertResource($resource);
        $this->assertEquals($this->testUser->id, $exercises->first()->user_id);
        $this->assertEquals('Close Grip Bench Press', $exercises->first()->name);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_update_another_users_exercise()
    {
        $user2 = factory(User::class)->create();
        $this->response = $this->actingAs($user2)->json("PUT", route('exercises.update', [
            'exercise' => $this->exercise->id
        ]), [
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 1
        ]);

        $this->response->assertStatus(403);
        $responseArray = $this->response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(
            route('exercises.update', ['exercise' => $this->exercise->id], false),
            $responseArray['errors']['source']['pointer']
        );
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }


    /**
     * @test
     */
    public function unauthenticated_user_cannot_update_an_exercise()
    {
        $this->response = $this->json("POST", route('exercises.store', [
        'exercise' => $this->exercise->id
        ]), [
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
        $this->response = $this->updateExercise([
            'exercise' => $this->exercise->id
        ], [
            'exercise_type' => 1
        ]);

        $this->assertFieldHasValidationError('name');
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

        $this->response = $this->updateExercise([
            'exercise' => $this->exercise->id
        ], [
            'name' => 'Close Grip Bench Press',
            'exercise_type' => 1
        ]);

        $this->assertFieldHasValidationError('name');
    }

    /**
     * Helper method to update an exercise via HTTP POST.
     *
     * @param $routeParams
     * @param $params
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function updateExercise($routeParams, $params)
    {
        return $this->actingAs($this->testUser)->json("PUT", route('exercises.update', $routeParams), $params);
    }
}
