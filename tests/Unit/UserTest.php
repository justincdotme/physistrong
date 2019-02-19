<?php

namespace Tests\Unit;

use App\Core\User;
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
        $exercises = collect([
            factory(Exercise::class)->make()
        ]);

        $this->user->setRelation('exercises', $exercises);

        $this->assertCount(1, $this->user->exercises);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $workouts = collect([
            factory(Workout::class)->make()
        ]);

        $this->user->setRelation('workouts', $workouts);

        $this->assertCount(1, $this->user->workouts);
    }
}
