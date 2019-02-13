<?php

namespace Tests\Unit;

use App\Core\Set;
use Tests\TestCase;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WorkoutTest extends TestCase
{
    use DatabaseMigrations;

    protected $sets;
    protected $workout;
    protected $exercise1;
    protected $exercise2;

    public function setUp()
    {
        parent::setUp();
        $this->workout = factory(Workout::class)->create();
        $this->exercise1 = factory(Exercise::class)->make([
            'name' => 'Bench Press',
            'id' => 1
        ]);
        $this->exercise2 = factory(Exercise::class)->make([
            'name' => 'Squat',
            'id' => 2
        ]);
        $this->sets = collect([
            factory(Set::class)->make([
                'weight' => 120,
                'count' => 10,
                'exercise_id' => $this->exercise1->id,
                'set_order' => 2
            ]),
            factory(Set::class)->make([
                'weight' => 140,
                'count' => 7,
                'exercise_id' => $this->exercise1->id,
                'set_order' => 1
            ]),
            factory(Set::class)->make([
                'weight' => 145,
                'count' => 6,
                'exercise_id' => $this->exercise1->id,
                'set_order' => 3
            ]),
            factory(Set::class)->make([
                'weight' => 120,
                'count' => 10,
                'exercise_id' => $this->exercise2->id,
                'set_order' => 2
            ]),
            factory(Set::class)->make([
                'weight' => 140,
                'count' => 7,
                'exercise_id' => $this->exercise2->id,
                'set_order' => 1
            ]),
            factory(Set::class)->make([
                'weight' => 145,
                'count' => 6,
                'exercise_id' => $this->exercise2->id,
                'set_order' => 3
            ]),
        ]);

        $this->workout->sets()->saveMany($this->sets);
    }

    /**
     * @test
     */
    public function sets_are_grouped_by_exercise()
    {
        $this->workout->getSets()
            ->first()
            ->each(function ($item, $key) {
                $this->assertEquals($this->exercise1->id, $item->exercise_id);
            }
        );
        $this->workout->getSets()
            ->last()
            ->each(function ($item, $key) {
                $this->assertEquals($this->exercise2->id, $item->exercise_id);
            }
        );
    }

    /**
     * @test
     */
    public function sets_are_ordered_by_set_order()
    {
        $exerciseOneCollection =  $this->workout->getSets()->first();
        $this->assertGreaterThan(
            $exerciseOneCollection->first()->set_order,
            $exerciseOneCollection->last()->set_order
        );

        $exerciseTwoCollection =  $this->workout->getSets()->last();
        $this->assertGreaterThan(
            $exerciseTwoCollection->first()->set_order,
            $exerciseTwoCollection->last()->set_order
        );
    }

    /**
     * @test
     */
    public function exercises_are_ordered_by_exercise_order()
    {

    }
}
