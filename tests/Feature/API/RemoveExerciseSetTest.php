<?php

namespace Tests\Feature\API;

use App\Core\Set;
use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RemoveExerciseSetTest extends TestCase
{
    use DatabaseMigrations;

    protected $set;
    protected $workout;
    protected $exercise;
    protected $testUser;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create();
        $this->workout = factory(Workout::class)->create([
            'user_id' => $this->testUser->id
        ]);
        $this->exercise = factory(Exercise::class)->create();
        $this->set = factory(Set::class)->create([
            'exercise_id' => $this->exercise->id,
            'workout_id' => $this->workout->id
        ]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_remove_set()
    {
        $response = $this->actingAs($this->testUser)->json("DELETE",
            route('sets.destroy', [
                'set' => $this->set->id
            ]), [
                'exercise_order' => 1
            ]
        );

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sets', [
            'id' => $this->set->id
        ]);
    }

    /**
     * @test
     */
    public function authenticated_user_can_not_remove_another_users_set()
    {
        $user2 = factory(User::class)->create();

        $response = $this->actingAs($user2)->json("DELETE",
            route('sets.destroy', [
                'set' => $this->set->id
            ])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('sets', [
            'id' => $this->set->id
        ]);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('403', $responseArray['errors']['status']);
        $this->assertEquals(
            route('sets.destroy', [
                'set' => $this->set->id
            ], false),
            $responseArray['errors']['source']['pointer']
        );
        $this->assertEquals('This action is unauthorized', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_remove_set()
    {
        $response = $this->json("DELETE",
            route('sets.destroy', [
                'set' => $this->set->id
            ])
        );

        $response->assertStatus(401);
        $this->assertDatabaseHas('sets', [
            'id' => $this->set->id
        ]);
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertEquals('401', $responseData['errors']['status']);
        $this->assertEquals('Missing token', $responseData['errors']['detail']);
        $this->assertEquals(route('sets.destroy', [
            'set' => $this->set->id,
        ], false), $responseData['errors']['source']['pointer']);
    }
}
