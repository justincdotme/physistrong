<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use App\Core\Exercise;
use App\Http\Resources\Exercise as ExerciseResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewExerciseTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;
    protected $exercise;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->exercise = factory(Exercise::class)->create([
            'user_id' => $this->testUser->id
        ]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_view_their_own_exercise()
    {
        $resource = new ExerciseResource($this->exercise);

        $this->response = $this->actingAs($this->testUser)->json("GET",
            route('exercises.show', [
                'exercise' => $this->exercise->id
            ])
        );

        $this->response->assertStatus(200);
        $this->response->assertResource($resource);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_view_another_users_exercise()
    {
        $user2 = factory(User::class)->create();
        $this->response = $this->actingAs($user2)->json("GET",
            route('exercises.show', [
                'exercise' => $this->exercise->id
            ])
        );

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
    public function unauthenticated_user_can_not_view_an_exercise()
    {
        $this->response = $this->json("GET",
            route('exercises.show', [
                'exercise' => $this->exercise->id
            ])
        );

        $this->response->assertStatus(401);
        $responseData = $this->response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertEquals('401', $responseData['errors']['status']);
        $this->assertEquals('Missing token', $responseData['errors']['detail']);
        $this->assertEquals(
            route('exercises.show', [
                'exercise' => $this->exercise->id
            ], false), $responseData['errors']['source']['pointer']
        );
    }
}
