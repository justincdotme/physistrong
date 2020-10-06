<?php

namespace Tests\Unit;

use App\Core\User;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use App\Core\ExerciseType;
use ExerciseTypesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExerciseTest extends TestCase
{
    use DatabaseMigrations;

    protected $exercise;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exercise = factory(Exercise::class)->make([
            'id' => 1,
            'name' => 'Squat',
            'exercise_type_id' => 1
        ]);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_workouts()
    {
        $workout = factory(Workout::class)->create();

        $this->exercise->workouts()->save($workout, ['exercise_order' => 1]);

        $this->assertCount(1, $this->exercise->workouts);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_exercise_type()
    {
        $this->seed(ExerciseTypesTableSeeder::class);

        $type = ExerciseType::find(1);
        $this->exercise->setRelation('type', $type);

        $this->assertEquals($type->id, $this->exercise->type->id);
        $this->assertEquals($type->name, $this->exercise->type->name);
    }

    /**
     * @test
     */
    public function it_has_relationship_to_user()
    {
        $user = factory(User::class)->make();

        $this->exercise->setRelation('user', $user);

        $this->assertEquals($user->id, $this->exercise->user->id);
        $this->assertEquals($user->name, $this->exercise->user->name);
    }

    /**
     * @test
     */
    public function it_can_get_formatted_measurement_type()
    {
        $this->seed(ExerciseTypesTableSeeder::class);

        $dynamicType = ExerciseType::find(1);
        $dynamicExercise = $this->exercise;
        $dynamicExercise->type()->associate($dynamicType)->save();

        $staticType = ExerciseType::find(2);
        $staticExercise = factory(Exercise::class)->make();
        $staticExercise->type()->associate($staticType)->save();

        $this->assertEquals('reps', $dynamicExercise->measurement);
        $this->assertEquals('seconds', $staticExercise->measurement);
    }
}
