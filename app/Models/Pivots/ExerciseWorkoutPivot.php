<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/** @property int $exercise_order */
class ExerciseWorkoutPivot extends Pivot
{
    public $timestamps = false;

    protected $table = 'exercise_workout';
}
