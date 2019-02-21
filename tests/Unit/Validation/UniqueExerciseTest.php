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

    /**
     * @test
     */
    public function user_can_add_unique_exercise_name()
    {
        $this->testUser = factory(User::class)->create([
            'id' => 1
        ]);
        $this->exercise = factory(Exercise::class)->create([
            'name' => 'Squat',
            'user_id' => $this->testUser->id
        ]);

        $v = $this->app['validator']->make(
            [
                'exercise' => 'Bench Press'
            ], [
            'exercise' => ['required', new UniqueExercise(new Exercise, $this->testUser)]
        ]);

        $this->assertTrue($v->passes());
    }

    /**
     * @test
     */
    public function user_can_not_add_duplicate_exercise_name()
    {
        $this->testUser = factory(User::class)->create([
            'id' => 1
        ]);
        $this->exercise = factory(Exercise::class)->create([
            'name' => 'Bench Press',
            'user_id' => $this->testUser->id
        ]);

        $v = $this->app['validator']->make([
            'exercise' => 'Bench Press'
        ], [
            'exercise' => ['required', new UniqueExercise(new Exercise, $this->testUser)]
        ]);

        $this->assertTrue($v->fails());
    }
}