<?php

namespace Tests\Unit;

use App\Core\User;
use ExerciseTypesTableSeeder;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserTest extends TestCase
{
    use DatabaseMigrations;

    protected $user;

    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    /**
     * @test
     */
    public function it_has_relationship_to_exercises()
    {
        $exercise = factory(Exercise::class)->make();

        $this->user->exercises()->save($exercise);

        $this->assertCount(1, $this->user->exercises);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $workout = factory(Workout::class)->make();

        $this->user->workouts()->save($workout);

        $this->assertCount(1, $this->user->workouts);
    }
}
