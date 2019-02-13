<?php

namespace Tests\Unit;

use App\Core\Exercise;
use App\Core\Set;
use App\Core\User;
use App\Core\Workout;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SetTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function forWorkout_returns_correct_values()
    {
        $weight = 120;
        $count = 10;
        $setOrder = 1;
        $user = factory(User::class)->create();
        $workout = factory(Workout::class)->create([
            'user_id' => $user->id
        ]);
        $exercise = factory(Exercise::class)->create([
            'name' => 'squat'
        ]);

        $set = Set::forWorkout($workout, $exercise, $setOrder, $weight, $count);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertEquals($workout->id, $set->workout->id);
        $this->assertEquals($exercise->id, $set->exercise->id);
        $this->assertEquals($setOrder, $set->set_order);
        $this->assertEquals($count, $set->count);
    }
}
