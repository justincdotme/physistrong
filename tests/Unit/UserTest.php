<?php

namespace Tests\Unit;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserTest extends TestCase
{
    use DatabaseMigrations;

    protected $user;

    protected function setUp(): void
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

    /**
     * @test
     */
    public function it_fires_event_when_created()
    {
        Event::fake([UserRegistered::class]);

        $user = factory(User::class)->create([
            'email' => 'test@user.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'abc123'
        ]);

        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
             return ($user->email === $event->user->email);
        });
    }

    /**
     * @test
     */
    public function it_hashes_password()
    {
        $user = factory(User::class)->create([
            'email' => 'test@user.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'abc123'
        ]);

        $this->assertTrue(Hash::check('abc123', $user->password));
    }
}
