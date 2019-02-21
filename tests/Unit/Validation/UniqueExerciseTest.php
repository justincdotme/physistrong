<?php

namespace Tests\Unit\Resources;

use App\Core\User;
use Tests\TestCase;
use App\Core\Exercise;
use App\Rules\UniqueExercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UniqueExerciseTest extends TestCase
{
    use DatabaseMigrations;

    protected $testUser;
    protected $exercise;

    public function setUp()
    {
        parent::setUp();
        $this->testUser = factory(User::class)->create([
            'id' => 1
        ]);
    }

    /**
     * @test
     */
    public function user_can_add_unique_exercise_name()
    {
        $this->exercise = factory(Exercise::class)->create([
            'name' => 'Squat',
            'user_id' => $this->testUser->id
        ]);

        $this->assertTrue(( new UniqueExercise(new Exercise, $this->testUser))->passes('exercise', 'Bench Press'));
    }

    /**
     * @test
     */
    public function user_can_not_add_duplicate_exercise_name()
    {
        $this->exercise = factory(Exercise::class)->create([
            'name' => 'Bench Press',
            'user_id' => $this->testUser->id
        ]);

        $this->assertFalse(( new UniqueExercise(new Exercise, $this->testUser))->passes('exercise', 'Bench Press'));
    }

    /**
     * @test
     */
    public function it_has_correct_message()
    {
        $this->assertEquals("Only 1 unique exercise name per user.", ( new UniqueExercise(new Exercise, $this->testUser))->message());
    }
}